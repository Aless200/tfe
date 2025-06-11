<?php

namespace App\Controller;

use App\Repository\GameRepository;
use App\Repository\TournamentRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TournamentController extends AbstractController
{
    #[Route('/tournoi', name: 'app_tournament')]
    public function allTournaments(TournamentRepository $repository, PaginatorInterface $paginator, Request $request): Response
    {
        $tournaments = $repository->findBy(['isPublished' => 1]);
        $pagination = $paginator->paginate($tournaments, $request->query->getInt('page', 1), 3);
        return $this->render('tournament/tournaments.html.twig', [
            'tournaments' => $pagination,
        ]);
    }

    #[Route('/tournoi/{slug}', name: 'app_detailTournament')]
    public function detailTournament(TournamentRepository $repository, string $slug): Response
    {
        $tournament = $repository->findOneBy(['slug' => $slug]);
        return $this->render('tournament/detailTournament.html.twig', [
            'tournament' => $tournament,
        ]);
    }

    #[Route('/tournoi/result/{slug}', name: 'app_detail_result')]
    public function detailResult(TournamentRepository $repository, GameRepository $gameRepository, string $slug): Response
    {
        $tournament = $repository->findOneBy(['slug' => $slug]);

        if (!$tournament) {
            throw $this->createNotFoundException('Le tournoi n\'existe pas.');
        }

        $matches = $gameRepository->findBy(['tournament' => $tournament]);

        // Supprimer les doublons
        $uniqueMatches = [];
        foreach ($matches as $match) {
            $key = $match->getTeam1()->getId() . '-' . $match->getTeam2()->getId() . '-' . $match->getRoundT();
            $uniqueMatches[$key] = $match;
        }
        $matches = array_values($uniqueMatches);

        // Grouper par round
        $gamesByRound = [];
        foreach ($matches as $match) {
            $round = $match->getRoundT();
            $gamesByRound[$round][] = $match;
        }

        return $this->render('tournament/detailResult.html.twig', [
            'tournamentResult' => $tournament,
            'gamesByRound' => $gamesByRound,
        ]);
    }


}
