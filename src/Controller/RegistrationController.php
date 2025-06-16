<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationType;
use App\Service\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, EmailVerifier $emailVerifier): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $userPasswordHasher->hashPassword($user, $form->get('password')->getData())
            )
                ->setVerificationToken(bin2hex(random_bytes(32)))
                ->setIsVerified(false)
                ->setRoles(['ROLE_USER'])
                ->setIsAnonymized(false)
                ->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($user);
            $entityManager->flush();

            // Envoyer l'email de vérification
            $emailVerifier->sendEmailConfirmation($user);

            // Redirigez l'utilisateur vers une page indiquant qu'un email de vérification a été envoyé
            return $this->redirectToRoute('app_check_email_registration');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, EntityManagerInterface $entityManager): Response
    {
        $id = $request->query->get('id');

        if (null === $id) {
            return $this->redirectToRoute('app_register');
        }

        $user = $entityManager->getRepository(User::class)->find($id);

        if (null === $user) {
            return $this->redirectToRoute('app_register');
        }

        // Vérifiez le jeton et activez l'utilisateur
        $user->setIsVerified(true);
        $user->setVerificationToken(null);
        $entityManager->flush();

        return $this->redirectToRoute('app_login');
    }

    #[Route('/check-email-registration', name: 'app_check_email_registration')]
    public function checkEmailRegistration(): Response
    {
        // Rendre une vue qui informe l'utilisateur de vérifier son email pour finaliser l'inscription
        return $this->render('registration/check_email_registration.html.twig');
    }

    private function generateUniqueFileName(): string
    {
        return md5(uniqid());
    }
}