<?php

namespace App\Controller;

use App\Repository\NewsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(NewsRepository $repository): Response
    {
        $lastNews = $repository->getNews();
        return $this->render('home/index.html.twig', [
            'lastNews' => $lastNews,
         ]);
    }
}
