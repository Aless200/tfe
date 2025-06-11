<?php

namespace App\Controller\Admin\news;

use App\Entity\News;
use App\Form\NewsType;
use App\Repository\NewsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class NewsAdminController extends AbstractController
{

    private $security;

    private $slugger;

    public function __construct(Security $security, sluggerInterface $slugger)
    {
        $this->security = $security;
        $this->slugger = $slugger;
    }




    #[Route('/admin/news', name: 'app_news_admin')]
    public function news(NewsRepository $repository, PaginatorInterface $paginator, Request $request): Response
    {
        $news = $repository->findAll();

        $page = $request->query->getInt('page', 1);
        $paginator = $paginator->paginate($news, $page, 6);
        return $this->render('admin/news/news.html.twig', [
            'news' => $paginator,
        ]);
    }

    #[Route('/admin/eyesnews/{id}', name: 'app_eyesnews_admin')]
    public function eyesnews(News $news, EntityManagerInterface $manager): Response
    {
        $news->setIsPublished(!$news->isPublished());
        $manager->persist($news);
        $manager->flush();
        return $this->render('admin/news/_statut.html.twig', [
            'news' => $news,
        ]);
    }

    #[Route('/admin/addnews', name: 'app_addnews_admin')]
    public function addnews(EntityManagerInterface $manager, Request $request): Response
    {
        $new = new News();
        $formNews = $this->createForm(NewsType::class, $new);
        $formNews->handleRequest($request);
        if ($formNews->isSubmitted() && $formNews->isValid()) {
            $user = $this->security->getUser();
            $new->setAuthor($user);
            $new->setIsPublished(true);
            $new->setCreatedAt(new \DateTimeImmutable());
            $new->setDatePublished(new \DateTimeImmutable());
            $new->setUpdatedAt(new \DateTimeImmutable());
            $new->setSlug($this->slugger->slug($new->getTitle()));

            $manager->persist($new);
            $manager->flush();
            $this->addFlash('admin_success', 'L\'ajout de l\'actualité à été enregistrer avec succès.');
            return $this->redirectToRoute('app_news_admin');
        }
        return $this->render('admin/news/addNews.html.twig', [
            'formNews' => $formNews
        ]);
    }


    #[Route('/admin/editnews/{id}', name: 'app_editnews_admin')]
    public function editNews(News $news, EntityManagerInterface $manager, Request $request): Response
    {
        $formEditNews = $this->createForm(NewsType::class, $news, [
            'validation_groups' => $news->getId() ? ['Default'] : ['create'],
        ]);

        $formEditNews->handleRequest($request);

        if ($formEditNews->isSubmitted() && $formEditNews->isValid()) {
            $user = $this->security->getUser();
            $news->setUpdatedAt(new \DateTimeImmutable());
            $news->setSlug($this->slugger->slug($news->getTitle()));
            $news->setAuthor($user);

            $manager->persist($news);
            $manager->flush();

            // Ajouter un message flash pour indiquer le succès
            $this->addFlash('admin_success', 'Les modifications de l\'actualité ont été enregistrées avec succès.');

            // Récupérer la page, le sort, et la direction de la requête
            $page = $request->query->getInt('page', 1);
            $sort = $request->query->get('sort', 'null');
            $direction = $request->query->get('direction', 'null');

            // Rediriger vers la page du tableau après la soumission réussie, en incluant page, sort, et direction
            return $this->redirectToRoute('app_news_admin', [
                'page' => $page,
                'sort' => $sort,
                'direction' => $direction,
            ]);
        }

        // Si le formulaire n'est pas soumis ou invalide, rendre la vue du formulaire
        return $this->render('admin/news/editNews.html.twig', [
            'formEditNews' => $formEditNews->createView(),
            'news' => $news,
        ]);
    }


}
