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
        // Créer un formulaire de filtrage par prix
        $form = $this->createFormBuilder()
            ->add('minPrice', \Symfony\Component\Form\Extension\Core\Type\NumberType::class, [
                'label' => 'Prix minimum',
                'required' => false,
                'attr' => ['placeholder' => 'Prix minimum'],
            ])
            ->add('maxPrice', \Symfony\Component\Form\Extension\Core\Type\NumberType::class, [
                'label' => 'Prix maximum',
                'required' => false,
                'attr' => ['placeholder' => 'Prix maximum'],
            ])
            ->add('filter', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, [
                'label' => 'Filtrer',
            ])
            ->getForm();

        $form->handleRequest($request);

        // Récupérer les paramètres de filtrage
        $minPrice = $form->isSubmitted() && $form->isValid() ? $form->get('minPrice')->getData() : null;
        $maxPrice = $form->isSubmitted() && $form->isValid() ? $form->get('maxPrice')->getData() : null;

        // Construire les critères de filtrage
        $criteria = [];
        if ($minPrice !== null) {
            $criteria[] = ['price' => ['>=', $minPrice]];
        }
        if ($maxPrice !== null) {
            $criteria[] = ['price' => ['<=', $maxPrice]];
        }

        // Récupérer les sweat-shirts avec les filtres
        if (!empty($criteria)) {
            $sweatshirts = $sweatshirtRepository->findByCriteria($criteria);
        } else {
            $sweatshirts = $sweatshirtRepository->findAll();
        }

        return $this->render('products/index.html.twig', [
            'sweatshirts' => $sweatshirts,
            'form' => $form->createView(),
        ]);
    }
}