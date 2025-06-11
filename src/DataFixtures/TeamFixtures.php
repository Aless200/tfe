<?php

namespace App\DataFixtures;

use App\Entity\Team;
use App\Entity\TeamUser;
use App\Entity\Tournament;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class TeamFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $users = $manager->getRepository(User::class)->findAll();
        $tournaments = $manager->getRepository(Tournament::class)->findAll();

        if (count($tournaments) === 0) {
            return;
        }

        // Choisir un tournoi au hasard
        $tournament = $faker->randomElement($tournaments);

        $usedUsers = [];  // Liste pour suivre les utilisateurs déjà assignés

        for ($i = 1; $i <= 16; $i++) { // Génère 16 équipes
            $team = new Team();
            $team->setTeamName('Équipe ' . $i);
            $team->setRound(1);

            // Assigner le tournoi choisi à l'équipe
            $team->setTournament($tournament);

            $manager->persist($team);

            // Ajoute 2 joueurs uniques par équipe
            $players = [];
            while (count($players) < 2) {  // Tant qu'on n'a pas 2 joueurs uniques
                $user = $faker->randomElement($users);
                // Vérifie que l'utilisateur n'a pas déjà été assigné à une autre équipe
                if (!in_array($user, $usedUsers)) {
                    $players[] = $user;
                    $usedUsers[] = $user;  // Marque l'utilisateur comme utilisé
                }
            }

            // Assigner les joueurs à l'équipe
            foreach ($players as $user) {
                $teamUser = new TeamUser();
                $teamUser->setTeam($team);
                $teamUser->setUser($user);
                $teamUser->setInvited(0);
                $teamUser->setTournament($tournament);
                $teamUser->setCreatedAt(new \DateTimeImmutable());

                $manager->persist($teamUser);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            TournamentsFixtures::class,
            UserFixtures::class,
        ];
    }
}

