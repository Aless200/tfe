<?php

namespace App\DataFixtures;

use App\Entity\Ground;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class GroundFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= 8; $i++) {
            $ground = new Ground();
            $ground->setNumber($i);
            $manager->persist($ground);
        }

        $manager->flush();
    }
}
