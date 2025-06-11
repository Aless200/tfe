<?php

namespace App\DataFixtures;

use App\Entity\Gallery;
use App\Entity\Pictures;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\File;
class GalleryFixtures extends Fixture implements DependentFixtureInterface
{

    public function __construct(private readonly sluggerInterface $slugger)
    {

    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_BE'); // Utilisation du locale belge
        $userRepository = $manager->getRepository(User::class);
        $allUsers = $userRepository->findAll();

        // Filtrer les utilisateurs avec les rôles souhaités
        $adminUsers = array_filter($allUsers, function (User $user) {
            return in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_SUPER_ADMIN', $user->getRoles());
        });

        // Nombre de galeries souhaitées
        $numberOfGalleries = 4;
        $imagesPerGallery = 6;

        // Créer des galeries
        for ($i = 0; $i < $numberOfGalleries; $i++) {
            $gallery = new Gallery();
            $randomAdmin = $adminUsers[array_rand($adminUsers)];
            $gallery->setTitle($faker->words(2, true))
                ->setDescription($faker->paragraphs(2, true))
                ->setCreatedAt(new \DateTimeImmutable())
                ->setIsPublished(1)
                ->setSlug($this->slugger->slug($gallery->getTitle()))
                ->setDatePublished(new \DateTimeImmutable())
                ->setAuthor($randomAdmin);

            $manager->persist($gallery);

            // Créer des images pour chaque galerie
            for ($j = 0; $j < $imagesPerGallery; $j++) {
                $picture = new Pictures();
                $imageNumber = ($i * $imagesPerGallery + $j) % 16; // Cycle à travers les images 0 à 15

                // Mettre à jour le chemin pour pointer vers assets/images/gallery/
                $imagePath = __DIR__ . '/../../assets/images/gallery/' . $imageNumber . '.jpg';

                // Vérifiez si le fichier existe avant de créer l'objet File
                if (file_exists($imagePath)) {
                    $imageFile = new File($imagePath);
                    $picture->setImageFile($imageFile);
                    $picture->setGallery($gallery); // Associer l'image à la galerie actuelle

                    $manager->persist($picture);
                } else {
                    // Gérer le cas où le fichier n'existe pas
                    echo "Le fichier $imagePath n'existe pas.\n";
                }
            }
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
