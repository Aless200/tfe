<?php

namespace App\Controller\Admin\user;

use App\Entity\TeamUser;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TeamAdminController extends AbstractController
{
    #[Route('/admin/team', name: 'app_team_admin')]
    public function teamUser(UserRepository $repository): Response
    {
        $teamUser = $repository->findTeamUser();
        return $this->render('admin/user/team.html.twig', [
            'teamUser' => $teamUser,
        ]);
    }

    #[Route('/admin/eyesTeam/{id}', name: 'app_admin_eyesTeam')]
    public function eyeTeam(User $user, EntityManagerInterface $manager): Response
    {
        $user->setIsDisabled(!$user->isDisabled());
        $manager->persist($user);
        $manager->flush();
        return $this->render('admin/user/_teamStatut.html.twig', [
                'user' => $user,
            ]
        );
    }

    #[Route('/admin/downgrade/{id}', name: 'app_admin_downgrade')]
    public function downgradeUser(User $user, EntityManagerInterface $manager): Response
    {
        // Vérifier que l'utilisateur connecté est bien SUPER_ADMIN
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        // Récupérer les rôles actuels
        $roles = $user->getRoles();

        // Filtrer pour supprimer ROLE_ADMIN s'il existe
        $newRoles = array_filter($roles, function($role) {
            return $role !== 'ROLE_ADMIN';
        });

        // Si l'utilisateur n'a plus de rôle, on lui ajoute ROLE_USER
        if (empty($newRoles)) {
            $newRoles[] = 'ROLE_USER';
        }

        // Mettre à jour les rôles
        $user->setRoles(array_values($newRoles));

        // Enregistrer en base de données
        $manager->persist($user);
        $manager->flush();

        // Rediriger vers la page de l'équipe ou retourner une réponse
        return $this->redirectToRoute('app_team_admin');
    }
}
