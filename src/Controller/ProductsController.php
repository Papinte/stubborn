<?php

namespace App\Controller;

use App\Repository\SweatshirtRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductsController extends AbstractController
{
    #[Route('/products', name: 'app_products')]
    public function index(SweatshirtRepository $sweatshirtRepository): Response
    {
        $sweatshirts = $sweatshirtRepository->findAll();

        return $this->render('products/index.html.twig', [
            'sweatshirts' => $sweatshirts,
        ]);
    }
}