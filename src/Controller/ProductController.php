<?php

namespace App\Controller;

use App\Entity\Sweatshirt;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductController extends AbstractController
{
    #[Route('/product/{id}', name: 'app_product')]
public function show(Request $request, Sweatshirt $sweatshirt): Response
{
    $form = $this->createFormBuilder()
        ->add('size', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
            'label' => 'Taille',
            'choices' => [
                'XS' => 'XS',
                'S' => 'S',
                'M' => 'M',
                'L' => 'L',
                'XL' => 'XL',
            ],
            'required' => true,
        ])
        ->add('submit', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, [
            'label' => 'Ajouter au panier',
        ])
        ->getForm();

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $data = $form->getData();
        $size = $data['size'];

        // Récupérer le panier depuis la session
        $cart = $request->getSession()->get('cart', []);

        // Ajouter l'article au panier (id, taille, prix, nom)
        $cart[] = [
            'id' => $sweatshirt->getId(),
            'name' => $sweatshirt->getName(),
            'size' => $size,
            'price' => $sweatshirt->getPrice(),
        ];

        // Sauvegarder le panier dans la session
        $request->getSession()->set('cart', $cart);

        $this->addFlash('success', "Le sweat-shirt {$sweatshirt->getName()} (taille {$size}) a été ajouté au panier !");

        return $this->redirectToRoute('app_cart');
    }

    return $this->render('products/show.html.twig', [
        'sweatshirt' => $sweatshirt,
        'form' => $form->createView(),
    ]);
}
}