<?php

namespace App\DataFixtures;

use App\Entity\News;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\String\Slugger\SluggerInterface;

class NewsFixtures extends Fixture implements DependentFixtureInterface
{

    public function __construct(private readonly sluggerInterface $slugger)
    {

    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $userRepository = $manager->getRepository(User::class);
        $allUsers = $userRepository->findAll();

        // Filtrer les utilisateurs avec les rôles souhaités
        $adminUsers = array_filter($allUsers, function (User $user) {
            return in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_SUPER_ADMIN', $user->getRoles());
        });

//        if (empty($adminUsers)) {
//            throw new \Exception('No admin users found. Cannot create news entries.');
//        } else {
//            echo "Found " . count($adminUsers) . " admin users.\n";
//        }

         for ($i = 0; $i < 10; $i++) {
             $news = new News();
             $randomAdmin = $adminUsers[array_rand($adminUsers)];
             $newsTitle = $faker->words(2, true);
             $news->setTitle($newsTitle)
                 ->setContent($faker->paragraphs(3, true))
                 ->setCreatedAt(new \DateTimeImmutable())
                 ->setIsPublished(1)
                 ->setSlug($this->slugger->slug($news->getTitle()))
                 ->setDatePublished(new \DateTimeImmutable())
                 ->setImage($i . '.jpg');
                 $news->setUpdatedAt(new \DateTimeImmutable());
                 $news->setAuthor($randomAdmin);
             $manager->persist($news);
         }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            TournamentsFixtures::class,
        ];
    }
}

