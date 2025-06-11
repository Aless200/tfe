<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Form\CommentType;
use App\Repository\NewsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class NewsController extends AbstractController
{
    #[Route('/actualites', name: 'app_actu')]
    public function actu(NewsRepository $repository, PaginatorInterface $paginator, Request $request): Response
    {
        $news = $repository->getFullNews();
        $pagination = $paginator->paginate($news, $request->query->getInt('page', 1), 6);
        return $this->render('news/news.html.twig', [
            'news' => $pagination,
        ]);
    }

    #[Route('/actualite/{slug}', name: 'app_detail_actu')]
    public function detailActualite(NewsRepository $repository, Request $request, string $slug, EntityManagerInterface $entityManager): Response
    {
        $news = $repository->findOneBy(['slug' => $slug]);
        $user = $this->getUser();

        // Vérifiez si l'utilisateur a déjà posté un commentaire pour cette actualité
        $existingComment = $news->getComments()->filter(function($comment) use ($user) {
            return $comment->getUser() === $user;
        })->first();

        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && !$existingComment) {
            $comment->setNews($news);
            $comment->setUser($user);
            $comment->setCreatedAt(new \DateTimeImmutable());
            $comment->setIsPublished(true); // ou false, selon votre logique

            $entityManager->persist($comment);
            $entityManager->flush();

            return $this->redirectToRoute('app_detail_actu', ['slug' => $slug]);
        }

        return $this->render('news/detailNews.html.twig', [
            'news' => $news,
            'commentForm' => $form->createView(),
            'hasCommented' => (bool) $existingComment,
        ]);
    }
}
