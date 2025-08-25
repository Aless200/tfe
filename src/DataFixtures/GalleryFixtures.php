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
use Symfony\Component\HttpFoundation\File\UploadedFile;

class GalleryFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly string $kernelProjectDir
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_BE');
        $userRepository = $manager->getRepository(User::class);
        $adminUsers = array_filter($userRepository->findAll(), fn (User $user) => in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_SUPER_ADMIN', $user->getRoles()));

        if (empty($adminUsers)) {
            echo "Attention: Aucun utilisateur administrateur trouvé. Les galeries ne seront pas créées.\n";
            return;
        }

        $fixtureDir = $this->kernelProjectDir . '/assets/images/fixtures/';
        $targetDir = $this->kernelProjectDir . '/public/uploads/images/gallery/';

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $numberOfGalleries = 4;
        $imagesPerGallery = 6;

        for ($i = 0; $i < $numberOfGalleries; $i++) {
            $gallery = new Gallery();
            $randomAdmin = $adminUsers[array_rand($adminUsers)];
            $galleryTitle = $faker->words(2, true);

            $gallery->setTitle($galleryTitle)
                ->setDescription($faker->paragraphs(2, true))
                ->setCreatedAt(new \DateTimeImmutable())
                ->setIsPublished(true)
                ->setSlug($this->slugger->slug($gallery->getTitle()))
                ->setDatePublished(new \DateTimeImmutable())
                ->setAuthor($randomAdmin);

            $manager->persist($gallery);

            for ($j = 0; $j < $imagesPerGallery; $j++) {
                $picture = new Pictures();
                $imageNumber = ($i * $imagesPerGallery + $j) + 1;
                $imageName = "petanque-" . $imageNumber . ".jpg";
                $fixtureImagePath = $fixtureDir . $imageName;
                $targetImagePath = $targetDir . $imageName;

                if (file_exists($fixtureImagePath)) {
                    copy($fixtureImagePath, $targetImagePath);
                    $uploadedFile = new UploadedFile($targetImagePath, $imageName, null, null, true);
                    $picture->setImageFile($uploadedFile);
                    $picture->setGallery($gallery);

                    $manager->persist($picture);
                } else {
                    echo "Le fichier $fixtureImagePath n'existe pas. Veuillez le placer dans le dossier des fixtures.\n";
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