<?php

namespace App\DataFixtures;

use App\Entity\News;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class NewsFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly string $kernelProjectDir
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $userRepository = $manager->getRepository(User::class);
        $adminUsers = array_filter($userRepository->findAll(), fn (User $user) => in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_SUPER_ADMIN', $user->getRoles()));

        if (empty($adminUsers)) {
            echo "Attention: Aucun utilisateur administrateur trouvé. Les actualités ne seront pas créées.\n";
            return;
        }

        $fixtureDir = $this->kernelProjectDir . '/assets/images/fixtures/';
        $targetDir = $this->kernelProjectDir . '/public/uploads/images/news/';

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        for ($i = 1; $i <= 10; $i++) {
            $news = new News();
            $randomAdmin = $adminUsers[array_rand($adminUsers)];
            $newsTitle = $faker->words(2, true);

            $imageName = "petanque-" . $i . ".jpg";
            $fixtureImagePath = $fixtureDir . $imageName;
            $targetImagePath = $targetDir . $imageName;

            if (file_exists($fixtureImagePath)) {
                copy($fixtureImagePath, $targetImagePath);
                $uploadedFile = new UploadedFile($targetImagePath, $imageName, null, null, true);
                $news->setImageFile($uploadedFile);
            }

            $news->setTitle($newsTitle)
                ->setContent($faker->paragraphs(3, true))
                ->setCreatedAt(new \DateTimeImmutable())
                ->setIsPublished(true)
                ->setSlug($this->slugger->slug($news->getTitle()))
                ->setDatePublished(new \DateTimeImmutable());
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