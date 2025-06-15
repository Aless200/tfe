<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        Security $security,
        EntityManagerInterface $manager
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Encodez le mot de passe
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('password')->getData()
                )
            )
                ->setRoles(['ROLE_USER'])
                ->setCreatedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable())
                ->setIsDisabled(false)
                ->setIsAnonymized(false);

            // Traitement de l'image
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile instanceof UploadedFile) {
                $newFilename = $this->generateUniqueFileName().'.'.$imageFile->guessExtension();

                // Déplacez le fichier
                $imageFile->move(
                    $this->getParameter('avatar_directory'),
                    $newFilename
                );

                // Mettez à jour l'entité
                $user->setImageAvatar($newFilename);
            } else {
                $user->setImageAvatar('default.jpg');
            }

            // Important: assurez-vous que imageFile est null
            $user->setImageFile(null);

            $manager->persist($user);
            $manager->flush();

            return $security->login($user, 'form_login', 'main');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    private function generateUniqueFileName(): string
    {
        return md5(uniqid());
    }
}