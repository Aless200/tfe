<?php

namespace App\Controller\Admin\gallery;

use App\Entity\Pictures;
use App\Form\PicturesType;
use App\Repository\GalleryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PictureAdminController extends AbstractController
{
    #[Route('/admin/ajoutimage/{id}', name: 'app_pictures_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        GalleryRepository $galleryRepository,
        int $id
    ): Response {
        $gallery = $galleryRepository->find($id);

        if (!$gallery) {
            throw $this->createNotFoundException('Galerie introuvable.');
        }

        $picture = new Pictures();
        $picture->setGallery($gallery);

        $form = $this->createForm(PicturesType::class, $picture);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($picture);
            $em->flush();

            $this->addFlash('success', 'Image ajoutée à la galerie !');

            return $this->redirectToRoute('app_pictures_new', [
                'id' => $gallery->getId(),
            ]);
        }

        return $this->render('pictures/new.html.twig', [
            'form' => $form->createView(),
            'gallery' => $gallery,
        ]);
    }
}
