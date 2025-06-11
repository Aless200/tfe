<?php

namespace App\Controller\Admin\tournaments;

use AllowDynamicProperties;
use App\Entity\Game;
use App\Entity\Ground;
use App\Entity\Team;
use App\Entity\TeamUser;
use App\Entity\Tournament;
use App\Form\TeamType;
use App\Form\TournamentType;
use App\Repository\TournamentRepository;
use App\Service\ArbitratorStorage;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Service\TournamentService;

#[AllowDynamicProperties]
class TournamentsAdminController extends AbstractController
{
    private $tournamentService;
    private SluggerInterface $slugger;

    public function __construct(SluggerInterface $slugger, TournamentService $tournamentService, RequestStack $requestStack)
    {
        $this->tournamentService = $tournamentService;
        $this->requestStack = $requestStack;
        $this->slugger = $slugger;
    }

    private function getSession(): SessionInterface
    {
        return $this->requestStack->getSession();
    }

    #[Route('/admin/tournois', name: 'app_admin_tournaments')]
    public function tournaments(TournamentRepository $repository, PaginatorInterface $paginator, Request $request, SessionInterface $session): Response
    {
        $this->session = $session;

        $query = $repository->findAll();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            3
        );

        return $this->render('admin/tournaments/tournaments.html.twig', [
            'tournaments' => $pagination,
        ]);
    }

    #[Route('/admin/eyestournaments/{id}', name: 'app_admin_eyestournaments')]
    public function eyestournaments(Tournament $tournament, EntityManagerInterface $manager): Response
    {
        $tournament->setIsPublished(!$tournament->isPublished());
        $manager->persist($tournament);
        $manager->flush();
        return $this->render('admin/tournaments/_tournamentsStatut.html.twig', [
                'tournament' => $tournament,
            ]
        );
    }

    #[Route('/admin/addTournament', name: 'app_admin_addTournament')]
    public function addTournament(Request $request, EntityManagerInterface $manager): Response
    {
        $tournament = new Tournament();
        $formTournament = $this->createForm(TournamentType::class, $tournament);
        $formTournament->handleRequest($request);
        if ($formTournament->isSubmitted() && $formTournament->isValid()) {
            dump($formTournament->getErrors(true));
            dump($tournament->getImageFile());
            $tournament->setCreatedAt(new \DateTimeImmutable());
            $tournament->setUpdatedAt(new \DateTimeImmutable());
            $tournament->setIsPublished(true);
            $tournament->setSlug($this->slugger->slug($tournament->getName()));
            $manager->persist($tournament);
            $manager->flush();

            $this->addFlash('admin_success', 'Le tournoi a été ajouté avec succès.');

            return $this->redirectToRoute('app_admin_tournaments');
        }
        return $this->render('admin/tournaments/addTournament.html.twig', [
            'formTournament' => $formTournament,
        ]);
    }

    private function extractStatusValue($status): ?string
    {
        if ($status === null) {
            return null;
        }

        // Si c'est une chaîne JSON (ex: "["annuler"]")
        if (is_string($status)) {
            $decoded = json_decode($status, true);
            return is_array($decoded) ? ($decoded[0] ?? null) : $status;
        }

        // Si c'est déjà un tableau PHP
        if (is_array($status)) {
            return $status[0] ?? null;
        }

        // Si c'est une valeur simple
        return $status;
    }

    #[Route('/admin/editTournament/{id}', name: 'app_admin_editTournament')]
    public function editTournament(
        Tournament $tournament,
        Request $request,
        EntityManagerInterface $manager,
        MailerInterface $mailer,
        LoggerInterface $logger
    ): Response {
        // Sauvegarde du statut actuel avant modification
        $oldStatus = $this->extractStatusValue($tournament->getStatus());

        $formEditTournament = $this->createForm(TournamentType::class, $tournament);
        $formEditTournament->handleRequest($request);

        if ($formEditTournament->isSubmitted() && $formEditTournament->isValid()) {
            // Mise à jour de la date de modification
            $tournament->setUpdatedAt(new \DateTimeImmutable());

            // Récupération du nouveau statut
            $newStatus = $this->extractStatusValue($tournament->getStatus());

            // Enregistrement des modifications
            $manager->flush();

            // Vérification si le statut est passé à "annuler"
            if ($newStatus === 'annuler' && $oldStatus !== 'annuler') {
                $logger->info('Tournoi annulé, envoi des notifications', [
                    'tournament_id' => $tournament->getId(),
                    'tournament_name' => $tournament->getName(),
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus
                ]);

                // Envoi des notifications aux joueurs
                $this->notifyPlayersOfCancellation($tournament, $mailer, $logger);

                $this->addFlash('admin_success', 'Le tournoi a été annulé et les joueurs ont été notifiés par email.');
            } else {
                $this->addFlash('admin_success', 'Le tournoi a été modifié avec succès.');
            }

            // Récupération des paramètres de pagination
            $page = $request->query->getInt('page', 1);
            $sort = $request->query->get('sort');
            $direction = $request->query->get('direction');

            return $this->redirectToRoute('app_admin_tournaments', [
                'page' => $page,
                'sort' => $sort,
                'direction' => $direction,
            ]);
        }

        return $this->render('admin/tournaments/editTournament.html.twig', [
            'formEditTournament' => $formEditTournament->createView(),
            'tournament' => $tournament,
        ]);
    }

    /**
     * Envoie des notifications aux joueurs inscrits au tournoi annulé
     */
    private function notifyPlayersOfCancellation(
        Tournament $tournament,
        MailerInterface $mailer,
        LoggerInterface $logger
    ): void {
        // Récupération des équipes du tournoi
        $teams = $tournament->getTeams();

        if ($teams->isEmpty()) {
            $logger->info('Aucune équipe inscrite au tournoi, pas de notification à envoyer', [
                'tournament_id' => $tournament->getId()
            ]);
            return;
        }

        // Collecte des emails des joueurs
        $emails = [];

        foreach ($teams as $team) {
            foreach ($team->getTeamUsers() as $teamUser) {
                $email = null;

                // Récupération de l'email (utilisateur enregistré ou invité)
                if ($teamUser->getUser()) {
                    $email = $teamUser->getUser()->getEmail();
                } elseif ($teamUser->getEmailGuest()) {
                    $email = $teamUser->getEmailGuest();
                }

                // Éviter les doublons d'emails
                if ($email && !in_array($email, $emails)) {
                    $emails[] = $email;
                }
            }
        }

        if (empty($emails)) {
            $logger->warning('Aucun email trouvé pour les joueurs du tournoi', [
                'tournament_id' => $tournament->getId(),
                'teams_count' => $teams->count()
            ]);
            return;
        }

        $successCount = 0;
        $errorCount = 0;

        // Envoi d'un email individuel à chaque joueur
        foreach ($emails as $emailAddress) {
            try {
                // Création de l'email
                $email = (new Email())
                    ->from('no-reply@petanqueclub.com')
                    ->to($emailAddress)
                    ->subject('Tournoi annulé : ' . $tournament->getName())
                    ->html($this->renderView('admin/tournaments/tournamentcancelled.html.twig', [
                        'tournament' => $tournament
                    ]));

                // Envoi de l'email
                $mailer->send($email);
                $successCount++;

                $logger->info('Email de notification envoyé avec succès', [
                    'tournament_id' => $tournament->getId(),
                    'player_email' => $emailAddress
                ]);

            } catch (\Exception $e) {
                $errorCount++;
                $logger->error('Erreur lors de l\'envoi de l\'email de notification', [
                    'tournament_id' => $tournament->getId(),
                    'player_email' => $emailAddress,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Log final du résultat
        $logger->info('Envoi des notifications terminé', [
            'tournament_id' => $tournament->getId(),
            'total_players' => count($emails),
            'success_count' => $successCount,
            'error_count' => $errorCount
        ]);
    }

    #[Route('/admin/showTournament/{id}', name: 'app_admin_showTournament')]
    public function showTournament(int $id, TournamentRepository $repository, EntityManagerInterface $manager, SessionInterface $session): Response
    {
        $tournament = $repository->find($id);
        if (!$tournament) {
            throw $this->createNotFoundException("Tournoi introuvable !");
        }

        // Récupérer tous les matchs du tournoi, triés par round
        $allMatches = $manager->getRepository(Game::class)->findBy(
            ['tournament' => $tournament],
            ['roundT' => 'ASC']
        );

        // Regrouper les matchs par tour
        $matchesByRound = [];
        foreach ($allMatches as $match) {
            $round = $match->getRoundT();
            $matchesByRound[$round][] = $match;
        }

        // Récupérer le round actuel depuis la session
        $currentRound = $this->getRoundFromSession($session, $id);

        $activeTeams = $manager->getRepository(Team::class)->findBy([
            'tournament' => $tournament,
            'round' => $currentRound
        ]);

        return $this->render('admin/tournaments/showTournament.html.twig', [
            'tournamentId' => $id,
            'matchesByRound' => $matchesByRound,
            'allMatches' => $allMatches,
            'currentRound' => $currentRound,
            'activeTeams' => $activeTeams,
            'isShuffled' => !empty($allMatches),
        ]);
    }

    #[Route('/admin/shuffleTeams/{id}', name: 'app_admin_shuffleTeams')]
    public function shuffleTeams(int $id, Request $request, TournamentRepository $repository, EntityManagerInterface $manager): Response
    {
        $session = $this->getSession();
        $tournament = $repository->find($id);
        if (!$tournament) {
            throw $this->createNotFoundException("Tournoi introuvable !");
        }

        // Vérifier qu'on est bien au tour 1
        $currentRound = $this->getRoundFromSession($session, $id);
        if ($currentRound !== 1) {
            throw new \Exception("Cette action n'est possible que pour le premier tour !");
        }

        // Récupérer les équipes du tournoi
        $teams = $tournament->getTeams()->toArray();
        if (empty($teams)) {
            throw new \Exception("Aucune équipe n'est inscrite au tournoi !");
        }

        // Calculer la puissance de 2 supérieure
        $teamCount = count($teams);
        $nextPowerOfTwo = pow(2, ceil(log($teamCount, 2)));
        $byeCount = $nextPowerOfTwo - $teamCount;

        // Ajouter les équipes BYE si nécessaire
        if ($byeCount > 0) {
            for ($i = 1; $i <= $byeCount; $i++) {
                $byeTeam = new Team();
                $byeTeam->setTeamName('équipe bye' . $i)
                    ->setIsBye(true)
                    ->setTournament($tournament);
                $manager->persist($byeTeam);
                $teams[] = $byeTeam;

                $tournament->addTeam($byeTeam);

            }
            $this->addFlash('admin_warning', "$byeCount équipe(s) BYE ajoutée(s) pour compléter à $nextPowerOfTwo équipes.");
        }
//        dd($teams);
//        dd($tournament->getTeams()->toArray());

        // Mélanger les équipes
        shuffle($teams);

        // Récupérer les terrains disponibles
        $grounds = $manager->getRepository(Ground::class)->findAll();
        if (empty($grounds)) {
            throw new \Exception("Aucun terrain disponible !");
        }

        // Générer les matchs du 1er tour
        $groundIndex = 0;
        for ($i = 0; $i < count($teams); $i += 2) {
            $team1 = $teams[$i];
            $team2 = $teams[$i + 1] ?? null;

            if (!$team2) {
                continue;
            }

            $game = new Game();
            $game->setTournament($tournament)
                ->setTeam1($team1)
                ->setTeam2($team2)
                ->setRoundT(1)
                ->setGround($grounds[$groundIndex % count($grounds)]);

            $manager->persist($game);
            $groundIndex++;
        }

        $manager->flush();

        // Mettre à jour la session
        $session->set('tournament_shuffled_' . $id, true);
        $session->set('tournament_round_' . $id, 1);

        $this->addFlash('admin_success', 'Le tirage au sort du premier tour a été effectué avec succès !');

        return $this->redirectToRoute('app_admin_showTournament', ['id' => $id]);
    }
    private function createByeTeam(EntityManagerInterface $manager): Team
    {
        $byeTeam = new Team();
        $byeTeam->setTeamName('BYE (Exempt)')
            ->setIsBye(true);

        $manager->persist($byeTeam);
        return $byeTeam;
    }

    private function getRoundFromSession(SessionInterface $session, int $tournamentId): int
    {
        return $session->get('tournament_round_' . $tournamentId, 1);
    }

    #[Route('/admin/update_score', name: 'app_admin_update_score', methods: ['POST'])]
    public function updateScore(Request $request, EntityManagerInterface $manager): JsonResponse
    {
        try {
            $data = $request->request->all();

            // Validation CSRF
            if (!$this->isCsrfTokenValid('update_score', $data['_token'] ?? '')) {
                return new JsonResponse(['status' => 'error', 'message' => 'Token CSRF invalide'], 400);
            }

            // Validation des données requises
            $requiredFields = ['gameId', 'tournamentId', 'round', 'team1Id', 'team2Id', 'scoreTeam1', 'scoreTeam2'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || $data[$field] === '') {
                    return new JsonResponse([
                        'status' => 'error',
                        'message' => "Le champ $field est manquant ou vide",
                        'receivedData' => $data
                    ], 400);
                }
            }

            $gameId = (int)$data['gameId'];
            $tournamentId = (int)$data['tournamentId'];
            $round = (int)$data['round'];
            $scoreTeam1 = (int)$data['scoreTeam1'];
            $scoreTeam2 = (int)$data['scoreTeam2'];

            // Validation des scores
            if ($scoreTeam1 < 0 || $scoreTeam2 < 0) {
                return new JsonResponse(['status' => 'error', 'message' => 'Les scores ne peuvent pas être négatifs'], 400);
            }

            if ($scoreTeam1 === $scoreTeam2) {
                return new JsonResponse(['status' => 'error', 'message' => 'Les scores ne peuvent pas être égaux'], 400);
            }

            // Récupérer le match
            $game = $manager->getRepository(Game::class)->find($gameId);
            if (!$game) {
                return new JsonResponse(['status' => 'error', 'message' => 'Match introuvable'], 404);
            }

            // Mettre à jour les scores
            $game->setScoreTeam1($scoreTeam1);
            $game->setScoreTeam2($scoreTeam2);

            // Déterminer l'équipe gagnante si le match est terminé
            $winner = null;
            if ($scoreTeam1 >= 13 || $scoreTeam2 >= 13) {
                $winner = $scoreTeam1 >= 13 ? $game->getTeam1() : $game->getTeam2();
                $winner->setRound($round + 1);
                $manager->persist($winner);
            }

            $manager->flush();

            $isTournamentCompleted = $this->isTournamentCompleted($tournamentId, $manager);
            return new JsonResponse([
                'status' => 'success',
                'gameId' => $game->getId(),
                'scoreTeam1' => $game->getScoreTeam1(),
                'scoreTeam2' => $game->getScoreTeam2(),
                'isCompleted' => ($game->getScoreTeam1() >= 13 || $game->getScoreTeam2() >= 13),
                'winnerTeamId' => $winner ? $winner->getId() : null,
                'nextRound' => $winner ? ($isTournamentCompleted ? null : $round + 1) : $round,
                'message' => 'Scores enregistrés avec succès',
                'tournamentCompleted' => $isTournamentCompleted
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Une erreur technique est survenue',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function isTournamentCompleted(int $tournamentId, EntityManagerInterface $manager): bool
    {
        // Récupérer tous les matchs du tournoi
        $matches = $manager->getRepository(Game::class)->findBy(['tournament' => $tournamentId], ['roundT' => 'DESC']);

        // Vérifier si tous les matchs du dernier tour sont terminés
        $lastRound = $matches[0]->getRoundT() ?? null;
        foreach ($matches as $match) {
            if ($match->getRoundT() == $lastRound && ($match->getScoreTeam1() < 13 && $match->getScoreTeam2() < 13)) {
                return false;
            }
        }

        return true;
    }

    private function generateNextRoundMatches(Tournament $tournament, int $round, EntityManagerInterface $manager)
    {
        // Récupérer uniquement les matchs du tournoi et du round actuel
        $matches = $manager->getRepository(Game::class)->findBy([
            'tournament' => $tournament,
            'roundT' => $round
        ]);

        $nextRoundTeams = [];

        // Déterminer les gagnants
        foreach ($matches as $match) {
            if ($match->getScoreTeam1() >= 13) {
                $winner = $match->getTeam1();
            } elseif ($match->getScoreTeam2() >= 13) {
                $winner = $match->getTeam2();
            } else {
                continue;
            }

            // Vérifier que l'équipe gagnante appartient bien au tournoi
            if ($winner->getTournament() === $tournament) {
                $nextRoundTeams[] = $winner;
            }
        }

        if (count($nextRoundTeams) > 1) {
            shuffle($nextRoundTeams);

            // Récupérer les terrains disponibles
            $grounds = $manager->getRepository(Ground::class)->findAll();
            if (empty($grounds)) {
                throw new \Exception("Aucun terrain disponible !");
            }

            // Générer les matchs pour le tour suivant
            for ($i = 0; $i < count($nextRoundTeams); $i += 2) {
                $team1 = $nextRoundTeams[$i];
                $team2 = $nextRoundTeams[$i + 1] ?? null;

                if (!$team2) {
                    // Cas où il y a un nombre impair d'équipes (normalement impossible en élimination directe)
                    continue;
                }

                $game = new Game();
                $game->setTournament($tournament)
                    ->setTeam1($team1)
                    ->setTeam2($team2)
                    ->setRoundT($round + 1)
                    ->setGround($grounds[$i % count($grounds)]);

                $manager->persist($game);
            }

            $manager->flush();
        }
    }


    #[Route('/admin/next_round/{id}', name: 'app_admin_next_round', methods: ['GET'])]
    public function nextRound(int $id, TournamentRepository $repository, EntityManagerInterface $manager, SessionInterface $session): Response
    {
        $tournament = $repository->find($id);
        if (!$tournament) {
            throw $this->createNotFoundException("Tournoi introuvable !");
        }

        $currentRound = $this->getRoundFromSession($session, $id);

        // Correction : On retire "tournament" et on cherche juste par "roundT"
        $matches = $manager->getRepository(Game::class)->findBy([
            'roundT' => $currentRound
        ]);

        foreach ($matches as $match) {
            if ($match->getScoreTeam1() === null || $match->getScoreTeam2() === null) {
                $this->addFlash('admin_error', 'Tous les matchs doivent être terminés avant de passer au tour suivant');
                return $this->redirectToRoute('app_admin_showTournament', ['id' => $id]);
            }
        }

        $this->generateNextRoundMatches($tournament, $currentRound, $manager);

        $session->set('tournament_round_' . $id, $currentRound + 1);

        return $this->redirectToRoute('app_admin_showTournament', ['id' => $id]);
    }

    #[Route('/admin/add-arbitrator', name: 'app_admin_add_arbitrator')]
    public function addArbitrator(Request $request, ArbitratorStorage $arbitratorStorage, EntityManagerInterface $manager): Response
    {
        if ($request->isMethod('POST')) {
            $username = $request->request->get('username');
            $password = $request->request->get('password');
            $tournamentId = (int) $request->request->get('tournamentId');

            $arbitratorStorage->addArbitrator($username, $password, $tournamentId);
            $this->addFlash('admin_success', 'Arbitre ajouté avec succès !');
            return $this->redirectToRoute('app_admin_tournaments');
        }

        // Récupérer les tournois pour le formulaire
        $tournaments = $manager->getRepository(Tournament::class)->findAll();
        return $this->render('admin/tournaments/add_arbitrator.html.twig', [
            'tournaments' => $tournaments,
        ]);
    }

    #[Route('/admin/tournament/{id}/add-team', name: 'app_admin_add_team_manual')]
    public function addTeamManual(Tournament $tournament, Request $request, EntityManagerInterface $manager): Response
    {
        if ($tournament->getTeams()->count() >= $tournament->getTeamsMax()) {
            $this->addFlash('admin_error', 'Nombre maximum d\'équipes atteint');
            return $this->redirectToRoute('app_admin_showTournament', ['id' => $tournament->getId()]);
        }

        $team = new Team();
        $isDoublette = in_array('doublette', $tournament->getTypeTournament());

        $form = $this->createForm(TeamType::class, $team, [
            'isDoublette' => $isDoublette,
            'is_admin' => true
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $team->setTournament($tournament);
            $team->setRound(1);
            $manager->persist($team);

            // Créer les TeamUser pour chaque joueur
            $players = [
                $form->get('player1')->getData(),
                $form->get('player2')->getData()
            ];

            if (!$isDoublette && $form->has('player3')) {
                $players[] = $form->get('player3')->getData();
            }

            foreach ($players as $playerName) {
                if (empty($playerName)) continue;

                $teamUser = new TeamUser();
                $teamUser->setTeam($team)
                    ->setTournament($tournament)
                    ->setCreatedAt(new \DateTimeImmutable())
                    ->setInvited(0)
                    ->setPlayerName($playerName); // Nouvelle propriété à ajouter dans TeamUser

                $manager->persist($teamUser);
            }

            $manager->flush();
            $this->addFlash('admin_success', 'L\'équipe a été ajouté avec succès.');

            // Récupérer les paramètres de la requête
            $page = $request->query->getInt('page', 1);
            $sort = $request->query->get('sort');
            $direction = $request->query->get('direction');

            // Rediriger en conservant les paramètres
            return $this->redirectToRoute('app_admin_tournaments', [
                'page' => $page,
                'sort' => $sort,
                'direction' => $direction,
            ]);
        }

        return $this->render('admin/tournaments/addTeam.html.twig', [
            'form' => $form->createView(),
            'tournament' => $tournament,
            'isDoublette' => $isDoublette
        ]);
    }

}
