<?php

// src/Controller/Admin/gallery/GalleryAdminController.php

namespace App\Controller\Admin\gallery;

use App\Entity\Gallery;
use App\Entity\Pictures;
use App\Form\GalleryType;
use App\Repository\GalleryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class GalleryAdminController extends AbstractController
{
    private $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    #[Route('/admin/gallery', name: 'app_gallery_admin')]
    public function allgallery(GalleryRepository $repository, PaginatorInterface $paginator, Request $request): Response
    {
        $galleries = $repository->findAll();
        $pagination = $paginator->paginate($galleries, $request->query->getInt('page', 1), 10);
        return $this->render('admin/gallery/gallery.html.twig', [
            'galleries' => $pagination,
        ]);
    }

    #[Route('/admin/eyesgallery/{id}', name: 'app_eyesgallery_admin')]
    public function eyeGallery(Gallery $gallery, EntityManagerInterface $manager): Response
    {
        $gallery->setIsPublished(!$gallery->isPublished());
        $manager->persist($gallery);
        $manager->flush();
        return $this->render('admin/gallery/_galleryStatut.html.twig', [
                'gallery' => $gallery,
            ]
        );
    }

    #[Route('/admin/addgallery', name: 'app_add_gallery_admin')]
    public function newGallery(Request $request, EntityManagerInterface $manager): Response
    {
        $gallery = new Gallery();
        $form = $this->createForm(GalleryType::class, $gallery);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $gallery->setCreatedAt(new \DateTimeImmutable());
            $gallery->setIsPublished(true);
            $gallery->setSlug($this->slugger->slug($gallery->getTitle()));
            $gallery->setAuthor($this->getUser());
            $manager->persist($gallery);
            $manager->flush();

            $this->addFlash('admin_success', 'Galerie ajouter avec succès');
            // Redirection vers le formulaire d'édition pour ajouter des images
            return $this->redirectToRoute('app_edit_gallery_admin', ['id' => $gallery->getId()]);
        }

        return $this->render('admin/gallery/addGallery.html.twig', [
            'gallery' => $gallery,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/editgallery/{id}', name: 'app_edit_gallery_admin')]
    public function editGallery(Request $request, Gallery $gallery, EntityManagerInterface $manager): Response
    {
        $form = $this->createForm(GalleryType::class, $gallery);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $removeImages = $request->request->all()['remove_images'] ?? [];

            foreach ($gallery->getPictures() as $picture) {
                if (in_array($picture->getId(), $removeImages)) {
                    $filePath = $this->getParameter('kernel.project_dir').'/assets/images/gallery/'.$picture->getImageName();
                    if (file_exists($filePath) && is_file($filePath)) {
                        unlink($filePath);
                    }
                    $gallery->removePicture($picture);
                }
            }

            $uploadedFiles = $request->files->all()['new_images'] ?? [];

            foreach ($uploadedFiles as $uploadedFile) {
                if ($uploadedFile instanceof UploadedFile) {
                    $filename = uniqid().'.'.$uploadedFile->guessExtension();
                    $uploadedFile->move(
                        $this->getParameter('kernel.project_dir').'/assets/images/gallery',
                        $filename
                    );

                    $picture = new Pictures();
                    $picture->setImageName($filename);
                    $picture->setGallery($gallery);

                    $manager->persist($picture);
                }
            }

            $manager->flush();

            $this->addFlash('admin_success', 'Galerie mise à jour avec succès');
            return $this->redirectToRoute('app_gallery_admin');
        }

        return $this->render('admin/gallery/editGallery.html.twig', [
            'form' => $form->createView(),
            'gallery' => $gallery,
        ]);
    }
}

