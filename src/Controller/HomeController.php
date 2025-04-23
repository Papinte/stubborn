<?php

namespace App\Controller;

use App\Repository\SweatshirtRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(SweatshirtRepository $sweatshirtRepository): Response
    {
        $featuredSweatshirts = $sweatshirtRepository->findBy(['isFeatured' => true]);

        return $this->render('home/index.html.twig', [
            'featuredSweatshirts' => $featuredSweatshirts,
        ]);
    }
}