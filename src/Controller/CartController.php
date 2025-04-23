<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CartController extends AbstractController
{
    #[Route('/cart', name: 'app_cart')]
    public function index(Request $request): Response
    {
        // Récupérer le panier depuis la session
        $cart = $request->getSession()->get('cart', []);

        // Calculer le prix total
        $totalPrice = 0;
        foreach ($cart as $item) {
            $totalPrice += $item['price'];
        }

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'totalPrice' => $totalPrice,
        ]);
    }

    #[Route('/cart/remove/{index}', name: 'app_cart_remove')]
    public function remove(Request $request, int $index): Response
    {
        // Récupérer le panier depuis la session
        $cart = $request->getSession()->get('cart', []);

        // Supprimer l'article à l'index donné
        if (isset($cart[$index])) {
            unset($cart[$index]);
            $cart = array_values($cart); // Réindexer le tableau
            $request->getSession()->set('cart', $cart);
            $this->addFlash('success', 'Article retiré du panier.');
        }

        return $this->redirectToRoute('app_cart');
    }
}