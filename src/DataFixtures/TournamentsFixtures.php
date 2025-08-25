<?php

namespace App\DataFixtures;

use App\Entity\Tournament;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\String\Slugger\SluggerInterface;

class TournamentsFixtures extends Fixture
{
    public function __construct(private readonly SluggerInterface $slugger)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $startDate = new \DateTimeImmutable('now');
        $types = ['doublette', 'triplette'];
        $randomType = $types[array_rand($types)];
        for ($i = 0; $i < 5; $i++) {
            $tournament = new Tournament();
            $tournament->setName($faker->words(2, true))
                ->setAdresse("Rue de la Salle Noiseux 5377")
                ->setDateTournament($startDate->modify('+' . ($i * 2) . 'weeks'))
                ->setIsPublished(true)
                ->setPrice($faker->randomFloat(2, 4, 12))
                ->setGroundMax(8)
                ->setTeamsMax(16)
                ->setSlug($this->slugger->slug($tournament->getName()))
                ->setStatus(['prochainement'])
                ->setCreatedAt(new \DateTimeImmutable('now'))
                ->setImageTournament('tournoi-' . ($i + 1) . '.jpg')
                ->setTypeTournament([$types[array_rand($types)]]);
            $tournament->setUpdatedAt(new \DateTimeImmutable('now'));

            $manager->persist($tournament);
        }
        $manager->flush();
    }
}