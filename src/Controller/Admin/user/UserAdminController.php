<?php

namespace App\Controller\Admin\user;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
}
