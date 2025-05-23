<?php

namespace App\Controller;

use App\Repository\SweatshirtRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductsController extends AbstractController
{
    #[Route('/products', name: 'app_products')]
    public function index(Request $request, SweatshirtRepository $sweatshirtRepository): Response
    {
        // Récupérer la fourchette de prix depuis les paramètres de requête
        $range = $request->query->get('range');

        // Définir les fourchettes de prix
        $priceRanges = [
            '10-29' => [10, 29],
            '29-35' => [29, 35],
            '35-50' => [35, 50],
        ];

        // Filtrer les sweat-shirts selon la fourchette sélectionnée
        if ($range && isset($priceRanges[$range])) {
            $minPrice = $priceRanges[$range][0];
            $maxPrice = $priceRanges[$range][1];
            $criteria = [
                ['price' => ['>=', $minPrice]],
                ['price' => ['<=', $maxPrice]],
            ];
            $sweatshirts = $sweatshirtRepository->findByCriteria($criteria);
        } else {
            $sweatshirts = $sweatshirtRepository->findAll();
        }

        return $this->render('products/index.html.twig', [
            'sweatshirts' => $sweatshirts,
            'selectedRange' => $range,
            'priceRanges' => array_keys($priceRanges),
        ]);
    }
}