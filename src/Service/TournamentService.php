<?php

namespace App\Service;

use App\Entity\Ground;
use App\Entity\Team;
use App\Entity\Game;
use App\Entity\Tournament;
use Doctrine\ORM\EntityManagerInterface;

class TournamentService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function shuffleTeams(int $round): array
    {
        $teams = $this->entityManager->getRepository(Team::class)->findBy(['round' => $round]);
        shuffle($teams);

        $matches = [];
        for ($i = 0; $i < count($teams) - 1; $i += 2) {
            $matches[] = [$teams[$i], $teams[$i + 1]];
        }

        return $matches;
    }

    public function updateScore(array $data): array
    {
        $game = $this->entityManager->getRepository(Game::class)->find($data['id']);
        $game->setScoreTeam1($data['scoreTeam1']);
        $game->setScoreTeam2($data['scoreTeam2']);

        if ($data['scoreTeam1'] >= 13) {
            $game->getTeam1()->setRound($game->getTeam1()->getRound() + 1);
        } elseif ($data['scoreTeam2'] >= 13) {
            $game->getTeam2()->setRound($game->getTeam2()->getRound() + 1);
        }

        $this->entityManager->flush();

        return ['status' => 'success'];
    }

    public function generateMatches(array $teams, int $round): array
    {
        shuffle($teams);
        $matches = [];

        for ($i = 0; $i < count($teams); $i += 2) {
            $matches[] = [
                'team1' => $teams[$i] ?? null,
                'team2' => $teams[$i + 1] ?? null,
                'round' => $round
            ];
        }

        return $matches;
    }

    public function areAllMatchesCompleted(array $matches): bool
    {
        foreach ($matches as $match) {
            if ($match->getScoreTeam1() === null || $match->getScoreTeam2() === null) {
                return false;
            }
            if ($match->getScoreTeam1() < 13 && $match->getScoreTeam2() < 13) {
                return false;
            }
        }
        return true;
    }

    public function generateNextRoundMatches(int $tournamentId, int $round, array $matches, EntityManagerInterface $manager)
    {
        $tournament = $manager->getRepository(Tournament::class)->find($tournamentId);
        $nextRoundTeams = [];

        foreach ($matches as $match) {
            if ($match->getScoreTeam1() >= 13) {
                $winner = $match->getTeam1();
            } else {
                $winner = $match->getTeam2();
            }
            $nextRoundTeams[] = $winner;
        }

        if (count($nextRoundTeams) > 1) {
            $newMatches = $this->generateMatches($nextRoundTeams, $round);
            foreach ($newMatches as $matchData) {
                $game = new Game();
                $game->setTournament($tournament);
                $game->setTeam1($matchData['team1']);
                $game->setTeam2($matchData['team2']);
                $game->setRoundT($round);

                // Attribuer un terrain disponible
                $allGrounds = $manager->getRepository(Ground::class)->findAll();
                if (isset($allGrounds[0])) {
                    $game->setGround($allGrounds[0]);
                }

                $manager->persist($game);
            }

            $manager->flush();
        }
    }
}