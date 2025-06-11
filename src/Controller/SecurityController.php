<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Si l'utilisateur est déjà connecté
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        // Récupère le message d'erreur, s'il y en a un
        $errorMessage = $error?->getMessage();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $errorMessage,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method will be intercepted by the logout key on your firewall.');
    }


// Route pour démarrer l'authentification via Google
//    #[Route('/connect/google', name: 'connect_google')]
//    public function connectGoogle(ClientRegistry $clientRegistry): Response
//    {
//// Redirige l'utilisateur vers Google pour se connecter
//        return $clientRegistry->getClient('google')->redirect();
//    }

// Route pour recevoir la redirection de Google après l'authentification
//    #[Route('/connect/google/check', name: 'connect_google_check')]
//    public function connectCheck(ClientRegistry $clientRegistry, Security $security): Response
//    {
//// Récupère le client Google
//        $client = $clientRegistry->getClient('google');
//
//        try {
//// Récupère l'utilisateur via l'OAuth client
//            $user = $client->fetchUser();
//
//// Vérifie si l'utilisateur existe déjà dans la base de données
//            $existingUser = $this->getDoctrine()->getRepository(User::class)->findOneBy(['googleId' => $user->getId()]);
//
//            if (!$existingUser) {
//// L'utilisateur n'existe pas encore dans la base de données, on doit l'enregistrer
//                $newUser = new User();
//                $newUser->setGoogleId($user->getId());
//                $newUser->setEmail($user->getEmail());
//                $newUser->setFullName($user->getName());
//
//// On peut ajouter des champs supplémentaires ici comme le prénom, la photo, etc.
//
//                $entityManager = $this->getDoctrine()->getManager();
//                $entityManager->persist($newUser);
//                $entityManager->flush();
//
//// Connecter l'utilisateur à Symfony
//                $this->get('security.token_storage')->setToken(new UsernamePasswordToken($newUser, null, 'main', $newUser->getRoles()));
//            } else {
//// L'utilisateur existe déjà, donc on le connecte
//                $this->get('security.token_storage')->setToken(new UsernamePasswordToken($existingUser, null, 'main', $existingUser->getRoles()));
//            }
//
//// Rediriger vers la page d'accueil ou une autre page après la connexion
//            return $this->redirectToRoute('app_home');
//        } catch (\Exception $e) {
//// Gérer les erreurs d'authentification
//            return $this->render('security/login.html.twig', [
//                'error' => 'Erreur de connexion : ' . $e->getMessage(),
//            ]);
//        }
//    }
}
