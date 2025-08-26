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

    // Au début de la classe
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

        // Récupérer l'ID du tournoi depuis la session
        $tournamentId = $session->get('arbitrator_tournament_id');
        if (!$tournamentId) {
            $this->addFlash('error', 'Aucun tournoi associé à cet arbitre.');
            return $this->redirectToRoute('app_arbitrator_login');
        }

        // Récupérer le tournoi
        $tournament = $entityManager->getRepository(Tournament::class)->find($tournamentId);
        if (!$tournament) {
            $this->addFlash('error', 'Tournoi introuvable.');
            return $this->redirectToRoute('app_arbitrator_login');
        }

        // Récupérer le round actuel (vous devrez peut-être stocker cette info en session)
        $currentRound = $this->getRoundFromSession($session, $tournamentId);

        // Récupérer les équipes actives du tournoi (celles du round actuel)
        $activeTeams = $entityManager->getRepository(Team::class)->findBy([
            'tournament' => $tournament,
            'round' => $currentRound
        ]);

        return $this->render('arbitration/entryScore.html.twig', [
            'teams' => $activeTeams, // On envoie les équipes actives
            'currentRound' => $currentRound // On envoie aussi le round actuel
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

        // Récupérer l'ID du tournoi depuis la session
        $tournamentId = $session->get('arbitrator_tournament_id');
        if (!$tournamentId) {
            $this->addFlash('error', 'Aucun tournoi associé à cet arbitre.');
            return $this->redirectToRoute('app_arbitrator_login');
        }

        $currentRound = $this->getRoundFromSession($session, $tournamentId);

        // Récupérer le tournoi
        $tournament = $entityManager->getRepository(Tournament::class)->find($tournamentId);
        if (!$tournament) {
            $this->addFlash('error', 'Tournoi introuvable.');
            return $this->redirectToRoute('app_arbitrator_login');
        }

        // Récupérer ou créer un match
        $game = $entityManager->getRepository(Game::class)->findOneBy([
            'tournament' => $tournament,
            'team1' => $team1Id,
            'team2' => $team2Id,
            'roundT' => $currentRound,
        ]);

        if (!$game) {
            $game = new Game();
            $game->setTournament($tournament);
            $game->setTeam1($entityManager->getReference('App\Entity\Team', $team1Id));
            $game->setTeam2($entityManager->getReference('App\Entity\Team', $team2Id));
            $game->setRoundT($currentRound); // 💡 NOUVEAU : On définit le round ici !
        }

        // Mettre à jour les scores
        $game->setScoreTeam1($scoreTeam1);
        $game->setScoreTeam2($scoreTeam2);

        $entityManager->persist($game);
        $entityManager->flush();

        $this->addFlash('success', 'Scores enregistrés avec succès.');
        return $this->redirectToRoute('app_arbitrator_score_entry');
    }
}
