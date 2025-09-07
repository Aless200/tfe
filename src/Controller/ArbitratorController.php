<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Ground;
use App\Entity\Team;
use App\Entity\Tournament;
use App\Service\ArbitratorStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;

class ArbitratorController extends AbstractController
{
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

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

        // 🏆 Correction : Déterminer le tour courant en cherchant le tour le plus élevé dans la base de données
        $highestRound = $entityManager->getRepository(Game::class)
            ->createQueryBuilder('g')
            ->select('MAX(g.roundT)')
            ->where('g.tournament = :tournament')
            ->setParameter('tournament', $tournament)
            ->getQuery()
            ->getSingleScalarResult();

        $currentRound = $highestRound ?? 1;

        // 🏆 Récupérer les matchs non terminés pour le tour le plus élevé
        $gamesToScore = $entityManager->getRepository(Game::class)
            ->createQueryBuilder('g')
            ->where('g.tournament = :tournament')
            ->andWhere('g.roundT = :round')
            ->andWhere('g.scoreTeam1 IS NULL OR g.scoreTeam2 IS NULL')
            ->setParameter('tournament', $tournament)
            ->setParameter('round', $currentRound)
            ->getQuery()
            ->getResult();

        $tournamentFinished = empty($gamesToScore);
        $grounds = $entityManager->getRepository(Ground::class)->findAll();

        return $this->render('arbitration/entryScore.html.twig', [
            'games' => $gamesToScore,
            'currentRound' => $currentRound,
            'tournamentFinished' => $tournamentFinished,
            'grounds' => $grounds
        ]);
    }

    #[Route('/arbitre/logout', name: 'app_arbitrator_logout')]
    public function logout(SessionInterface $session): Response
    {
        $session->remove('arbitrator_logged_in');
        $session->remove('arbitrator_tournament_id');
        return $this->redirectToRoute('app_arbitrator_login');
    }

    #[Route('/arbitre/save-score', name: 'app_arbitrator_save_score', methods: ['POST'])]
    public function saveScore(Request $request, SessionInterface $session, EntityManagerInterface $entityManager): Response
    {
        $gameId  = $request->request->get('gameId');
        $score1  = (int) $request->request->get('scoreTeam1');
        $score2  = (int) $request->request->get('scoreTeam2');
        $groundId = $request->request->get('ground');

        if (!$session->get('arbitrator_logged_in')) {
            return $this->redirectToRoute('app_arbitrator_login');
        }

        // Vérifications basiques
        if ($score1 < 0 || $score2 < 0 || $score1 > 13 || $score2 > 13) {
            $this->addFlash('error', 'Scores invalides.');
            return $this->redirectToRoute('app_arbitrator_score_entry');
        }
        if ($score1 === $score2) {
            $this->addFlash('error', 'Match nul interdit.');
            return $this->redirectToRoute('app_arbitrator_score_entry');
        }
        if (($score1 === 13 && $score2 > 11) || ($score2 === 13 && $score1 > 11)) {
            $this->addFlash('error', 'Quand une équipe atteint 13, l’autre ne peut pas dépasser 11.');
            return $this->redirectToRoute('app_arbitrator_score_entry');
        }
        if ($score1 < 13 && $score2 < 13) {
            $this->addFlash('error', 'Une équipe doit atteindre 13 points.');
            return $this->redirectToRoute('app_arbitrator_score_entry');
        }

        try {
            $ground       = $groundId ? $entityManager->getRepository(Ground::class)->find($groundId)
                : $entityManager->getRepository(Ground::class)->findOneBy([]);

            if (!$ground) {
                $this->addFlash('error', 'Terrain introuvable.');
                return $this->redirectToRoute('app_arbitrator_score_entry');
            }

            // 🏆 Remplacer la recherche par une simple récupération via l'ID
            $game = $entityManager->getRepository(Game::class)->find($gameId);

            if (!$game) {
                $this->addFlash('error', 'Match introuvable.');
                return $this->redirectToRoute('app_arbitrator_score_entry');
            }

            // Mise à jour des scores et du terrain
            $game->setScoreTeam1($score1);
            $game->setScoreTeam2($score2);
            $game->setGround($ground);

            $entityManager->flush();

            $this->addFlash('success', 'Score enregistré avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_arbitrator_score_entry');
    }
}
