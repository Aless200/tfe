<?php

namespace App\Controller\Admin\user;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserAdminController extends AbstractController
{
    #[Route('/admin/users', name: 'app_user_admin')]
    public function users(UserRepository $repository, PaginatorInterface $paginator, Request $request): Response
    {
        $users = $repository->findUser();

        $pagination = $paginator->paginate($users, $request->query->getInt('page', 1), 10);
        return $this->render('admin/user/users.html.twig', [
            'users' => $pagination,
            ]
        );
    }

    #[Route('/admin/eyesuser/{id}', name: 'app_eyesuser_admin')]
    public function eyeUser(User $user, EntityManagerInterface $manager): Response
    {
        $user->setIsDisabled(!$user->isDisabled());
        $manager->persist($user);
        $manager->flush();
        return $this->render('admin/user/_userStatut.html.twig', [
            'user' => $user,
            ]
        );
    }


    #[Route('/admin/promote/{id}', name: 'app_promote_admin')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function promoteUser(User $user, EntityManagerInterface $manager, UserRepository $repository): Response
    {
        $user->setRoles(['ROLE_ADMIN']);
        $manager->persist($user);
        $manager->flush();

        $repository->findTeamUser();

        return $this->redirectToRoute('app_team_admin');
    }


    #[Route('/admin/anonymize/{id}', name: 'app_anonymize_admin')]
    public function anonymizeUser(User $user, EntityManagerInterface $manager): Response
    {
        if ($user->isAnonymized()) {
            $this->addFlash('warning', 'Cet utilisateur est déjà anonymisé.');
            return $this->redirectToRoute('app_user_admin');
        }

        $user->anonymize();
        $manager->persist($user);
        $manager->flush();

        $this->addFlash('admin_success', 'L\'utilisateur a été anonymisé avec succès.');

        return $this->redirectToRoute('app_user_admin');
    }

    #[Route('/admin/user/export/{id}', name: 'app_user_export')]
    public function exportUserData(User $user): Response
    {
        // Création des données utilisateur au format CSV
        $userData = [
            ['ID', $user->getId()],
            ['Email', $user->getEmail()],
            ['Prénom', $user->getFirstName()],
            ['Nom', $user->getLastName()],
            ['Téléphone', $user->getPhoneNumber()],
            ['Roles', implode(', ', $user->getRoles())],
            ['Date de création', $user->getCreatedAt() ? $user->getCreatedAt()->format('Y-m-d H:i:s') : ''],
            ['Statut', $user->isDisabled() ? 'Désactivé' : 'Activé'],
            ['Anonymisé', $user->isAnonymized() ? 'Oui' : 'Non'],
        ];

        // Conversion en CSV
        $csvData = "Propriété,Valeur\n"; // Ajout de l'en-tête
        foreach ($userData as $row) {
            $csvData .= '"' . str_replace('"', '""', $row[0]) . '","' . str_replace('"', '""', $row[1]) . '"' . "\n";
        }

        // Création de la réponse avec le fichier CSV
        $response = new Response($csvData);
        $response->headers->set('Content-Type', 'text/csv');
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'donnees_utilisateur_' . $user->getId() . '_' . date('Y-m-d') . '.csv'
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }
}
