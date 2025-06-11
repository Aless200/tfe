<?php

namespace App\Controller\Admin\comment;

use App\Entity\Comment;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

class AdminCommentController extends AbstractController
{
    #[Route('/admin/comments', name: 'app_admin_comments')]
    public function index(CommentRepository $repository, PaginatorInterface $paginator, Request $request): Response
    {
//        $query = $repository->('c')
//            ->('c.createdAt', 'DESC')
//            ->getQuery();
        $comments = $repository->findAll();

        $page = $request->query->getInt('page', 1);
        $paginator = $paginator->paginate($comments, $page, 6);
        return $this->render('admin/comment/comments.html.twig', [
            'comments' => $paginator,
        ]);
    }


    #[Route('/admin/eyescomment/{id}', name: 'app_eyescomment_admin', methods: ['POST'])]
    public function eyescomment(Comment $comment, EntityManagerInterface $manager, MailerInterface $mailer, Request $request): Response
    {
        $wasPublished = $comment->isPublished();
        $comment->setIsPublished(!$wasPublished);

        $manager->flush();

        if ($wasPublished && !$comment->isPublished()) {
            $user = $comment->getUser();
            if ($user && $user->getEmail()) {
                $reason = $request->request->get('reason', 'Violation de nos règles de communauté');


                $email = (new Email())
                    ->from('noreply@votresite.com')
                    ->to($user->getEmail())
                    ->subject('Votre commentaire a été désactivé')
                    ->html($this->renderView('admin/comment/email/comment_disabled.html.twig', [
                        'comment' => $comment,
                        'reason' => $reason
                    ]));

                $mailer->send($email);
            }
        }

        return $this->render('admin/comment/_statut.html.twig', [
            'comment' => $comment,
        ]);
    }

}
