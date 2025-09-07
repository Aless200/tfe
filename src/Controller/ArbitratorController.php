<?php
namespace App\Controller;

use App\Entity\Game;
use App\Entity\Team;
use App\Entity\Tournament;
use App\Service\ArbitratorStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class ArbitratorController extends AbstractController
{
    #[Route('/arbitres/login', name: 'app_arbitrator_login')]
    public function login(Request $request, SessionInterface $session, ArbitratorStorage $arbitratorStorage): Response
    {
        if ($request->isMethod('POST')) {
            $username = $request->request->get('username');
            $password = $request->request->get('password');
            $arbitrator = $arbitratorStorage->findArbitrator($username);
            if ($arbitrator && $arbitrator['password'] === $password) {
                $session->set('arbitrator_logged_in', true);
                $session->set('arbitrator_tournament_id', $arbitrator['tournamentId']);
                $this->addFlash('success', 'Connexion réussie !');
                return $this->redirectToRoute('app_arbitrator_score_entry');
            }
            $this->addFlash('error', 'Identifiants invalides.');
            return $this->redirectToRoute('app_arbitrator_login');
        }
        return $this->render('arbitration/loginArbitrator.html.twig');
    }

    private function getRoundFromSession(SessionInterface $session, int $tournamentId): int
    {
        return $session->get('tournament_round_' . $tournamentId, 1);
    }

    #[Route('/arbitre/score-entry', name: 'app_arbitrator_score_entry')]
    public function scoreEntry(SessionInterface $session, EntityManagerInterface $entityManager): Response
    {
        if (!$session->get('arbitrator_logged_in')) {
            return $this->redirectToRoute('app_arbitrator_login');
        }

        $tournamentId = $session->get('arbitrator_tournament_id');
        if (!$tournamentId) {
            $this->addFlash('error', 'Aucun tournoi associé à cet arbitre.');
            return $this->redirectToRoute('app_arbitrator_login');
        }

        $tournament = $entityManager->getRepository(Tournament::class)->find($tournamentId);
        if (!$tournament) {
            $this->addFlash('error', 'Tournoi introuvable.');
            return $this->redirectToRoute('app_arbitrator_login');
        }

        // Vérifier si le tournoi est terminé en utilisant l'attribut status
        $tournamentStatus = $tournament->getStatus();
        $tournamentFinished = in_array('terminé', $tournamentStatus);

        if ($tournamentFinished) {
            return $this->render('arbitration/entryScore.html.twig', [
                'tournamentFinished' => true,
            ]);
        }

        $currentRound = $this->getRoundFromSession($session, $tournamentId);
        $activeTeams = $entityManager->getRepository(Team::class)->findBy([
            'tournament' => $tournament,
            'round' => $currentRound
        ]);

        return $this->render('arbitration/entryScore.html.twig', [
            'teams' => $activeTeams,
            'currentRound' => $currentRound,
            'tournamentFinished' => false,
        ]);
    }

    #[Route('/arbitre/logout', name: 'app_arbitrator_logout')]
    public function logout(SessionInterface $session): Response
    {
        $session->remove('arbitrator_logged_in');
        return $this->redirectToRoute('app_arbitrator_login');
    }

    #[Route('/arbitre/save-score', name: 'app_arbitrator_save_score', methods: ['POST'])]
    public function saveScore(Request $request, SessionInterface $session, EntityManagerInterface $entityManager): Response
    {
        if (!$session->get('arbitrator_logged_in')) {
            return $this->redirectToRoute('app_arbitrator_login');
        }

        $team1Id = $request->request->get('team1');
        $team2Id = $request->request->get('team2');
        $scoreTeam1 = $request->request->get('scoreTeam1');
        $scoreTeam2 = $request->request->get('scoreTeam2');

        $tournamentId = $session->get('arbitrator_tournament_id');
        if (!$tournamentId) {
            $this->addFlash('error', 'Aucun tournoi associé à cet arbitre.');
            return $this->redirectToRoute('app_arbitrator_login');
        }

        $tournament = $entityManager->getRepository(Tournament::class)->find($tournamentId);
        if (!$tournament) {
            $this->addFlash('error', 'Tournoi introuvable.');
            return $this->redirectToRoute('app_arbitrator_login');
        }

        // Vérifier si le tournoi est terminé en utilisant l'attribut status
        $tournamentStatus = $tournament->getStatus();
        if (in_array('terminé', $tournamentStatus)) {
            $this->addFlash('error', 'Ce tournoi est terminé. Vous ne pouvez plus entrer de scores.');
            return $this->redirectToRoute('app_arbitrator_score_entry');
        }

        $currentRound = $this->getRoundFromSession($session, $tournamentId);

        // Recherche ou création de l'entité Game
        $game = $entityManager->getRepository(Game::class)->findOneBy([
            'tournament' => $tournament,
            'team1' => $team1Id,
            'team2' => $team2Id,
            'roundT' => $currentRound,
        ]);

        if (!$game) {
            $game = new Game();
            $game->setTournament($tournament);
            $game->setTeam1($entityManager->getReference(Team::class, $team1Id));
            $game->setTeam2($entityManager->getReference(Team::class, $team2Id));
            $game->setRoundT($currentRound);
        }

        // Mise à jour des scores
        $game->setScoreTeam1($scoreTeam1);
        $game->setScoreTeam2($scoreTeam2);

        $entityManager->persist($game);
        $entityManager->flush();

        $this->addFlash('success', 'Scores enregistrés avec succès.');
        return $this->redirectToRoute('app_arbitrator_score_entry');
    }
}
