<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Form\CommentType;
use App\Repository\GalleryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GalleryController extends AbstractController
{
    #[Route('/galeries', name: 'app_gallery')]
    public function gallery(GalleryRepository $repository, PaginatorInterface $paginator, Request $request): Response
    {
        $galleries = $repository->getFullGallery();
        $pagination = $paginator->paginate($galleries, $request->query->getInt('page', 1), 3);
        return $this->render('gallery/gallery.html.twig', [
            'galleries' => $pagination,
        ]);
    }

    #[Route('/galerie/{slug}', name: 'app_detail_gallery', methods: ['GET', 'POST'])]
    public function detailGallery(string $slug, GalleryRepository $repository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $gallery = $repository->findOneBy(['slug' => $slug]);

        if (!$gallery) {
            throw $this->createNotFoundException('La galerie n\'existe pas.');
        }

        $user = $this->getUser();

        // Vérifiez si l'utilisateur a déjà posté un commentaire pour cette galerie
        $existingComment = $gallery->getComments()->filter(function($comment) use ($user) {
            return $comment->getUser() === $user;
        })->first();

        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && !$existingComment) {
            $comment->setGallery($gallery);
            $comment->setUser($user);
            $comment->setCreatedAt(new \DateTimeImmutable());
            $comment->setIsPublished(true); // ou false, selon votre logique

            $entityManager->persist($comment);
            $entityManager->flush();

            return $this->redirectToRoute('app_detail_gallery', ['slug' => $slug]);
        }

        return $this->render('gallery/detailGallery.html.twig', [
            'gallery' => $gallery,
            'commentForm' => $form->createView(),
            'hasCommented' => (bool) $existingComment,
        ]);
    }
}
