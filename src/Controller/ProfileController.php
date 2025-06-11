<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordType;
use App\Form\RegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;
use Vich\UploaderBundle\Entity\File;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function viewProfile(): Response
    {
        $user = $this->getUser();

        return $this->render('profile/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/profile/editProfile', name: 'app_editProfile')]
    public function editProfile(Request $request, EntityManagerInterface $manager, Security $security): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(RegistrationType::class, $user, [
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setUpdatedAt(new \DateTimeImmutable());

            // Vérifiez et supprimez l'objet File avant la sérialisation
            if ($user->getImageFile() instanceof File) {
                $user->setImageFile(null);
            }

            $manager->flush();

            // Utilisez le service Security pour accéder au token
            $token = $security->getToken();
            if ($token && $token->getUser() instanceof User) {
                $token->getUser()->setImageFile(null);
            }

            $this->addFlash('success', 'Votre profil a bien été mis à jour');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/editProfile.html.twig', [
            'formEditProfile' => $form->createView()
        ]);
    }

    #[Route('/editpassword', name: 'app_edit_password')]
    public function editPassword(Request $request, EntityManagerInterface $manager, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $user = $this->getUser();
        $formPassword = $this->createForm(ChangePasswordType::class, $user);
        $formPassword->handleRequest($request);
        if ($formPassword->isSubmitted() && $formPassword->isValid()) {
            $oldPassword = $formPassword->get('oldPassword')->getData();
            if (!$userPasswordHasher->isPasswordValid($user, $oldPassword)) {
                $this->addFlash(
                    'error',
                    'Mot de passe incorrect.'
                );
                return $this->render('profile/editPassword.html.twig', [
                    'formPassword' => $formPassword,
                ]);
            }

            $plainPassword = $formPassword->get('password')->getData();
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            $user->setUpdatedAt(new \DateTimeImmutable());
            $manager->persist($user);
            $manager->flush();
            $this->addFlash(
                'success',
                'Mot de passe modifié.'
            );
            return $this->redirectToRoute('app_profile');
        }
        return $this->render('profile/editpassword.html.twig', [
            'formPassword' => $formPassword,
        ]);
    }
}
