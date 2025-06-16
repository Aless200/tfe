<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private object $hasher;
    private array $genders = ['male', 'female'];
    private array $maleAvatars = [];
    private array $femaleAvatars = [];

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
        $this->initializeAvatars();
    }

    private function initializeAvatars(): void
    {
        // Générer la liste des avatars masculins (010m.jpg à 076m.jpg)
        for ($i = 10; $i <= 76; $i++) {
            $this->maleAvatars[] = sprintf('%03dm.jpg', $i);
        }

        // Générer la liste des avatars féminins (010f.jpg à 076f.jpg)
        for ($i = 10; $i <= 76; $i++) {
            $this->femaleAvatars[] = sprintf('%03df.jpg', $i);
        }
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_BE');

        // Fixtures pour les utilisateurs normaux
        for ($i = 1; $i <= 40; $i++) {
            $user = new User();
            $gender = $faker->randomElement($this->genders);

            // Sélectionner un avatar aléatoire selon le genre
            $avatar = ($gender === 'male')
                ? $faker->randomElement($this->maleAvatars)
                : $faker->randomElement($this->femaleAvatars);

            $user->setFirstName($faker->firstName($gender))
                ->setLastName($faker->lastName)
                ->setEmail($faker->email)
                ->setImageAvatar($avatar) // Utilisation de l'avatar sélectionné
                ->setPassword($this->hasher->hashPassword($user, 'password'))
                ->setCreatedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable())
                ->setRoles(['ROLE_USER'])
                ->setIsDisabled(0)
                ->setIsAnonymized(0)
                ->setIsVerified(true)
                ->setPhoneNumber($faker->regexify('\0 4\d{2} \d{2} \d{2} \d{2}'));

            $manager->persist($user);
        }

        // Compte Admin
        $admin = new User();
        $admin->setFirstName('John')
            ->setLastName('Doe')
            ->setEmail('john.doe@gmail.com')
            ->setImageAvatar($faker->randomElement($this->maleAvatars)) // Avatar masculin aléatoire
            ->setIsDisabled(0)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable())
            ->setIsAnonymized(0)
            ->setIsVerified(true)
            ->setRoles(['ROLE_ADMIN'])
            ->setPassword($this->hasher->hashPassword($admin, 'password'))
            ->setPhoneNumber($faker->regexify('\+32 4\d{2} \d{2} \d{2} \d{2}'));
        $manager->persist($admin);


        // Compte Super Admin
        $superAdmin = new User();
        $superAdmin->setFirstName('Alessandro')
            ->setLastName('Simal')
            ->setEmail('aless.simal@gmail.com')
            ->setImageAvatar($faker->randomElement($this->maleAvatars)) // Avatar masculin aléatoire
            ->setIsDisabled(0)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable())
            ->setIsAnonymized(0)
            ->setRoles(['ROLE_SUPER_ADMIN'])
            ->setIsVerified(true)
            ->setPassword($this->hasher->hashPassword($superAdmin, 'password'))
            ->setPhoneNumber($faker->regexify('\+32 4\d{2} \d{2} \d{2} \d{2}'));
        $manager->persist($superAdmin);

        $manager->flush();
    }
}