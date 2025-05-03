<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Sweatshirt;
use App\Entity\Stock;
use App\Service\StripeService;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

class CartController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/cart', name: 'app_cart')]
    public function index(Request $request): Response
    {
        // Récupérer le panier depuis la session
        $cart = $request->getSession()->get('cart', []);

        // Calculer le prix total
        $totalPrice = 0;
        foreach ($cart as $item) {
            if (!isset($item['price']) || !is_numeric($item['price'])) {
                throw new \LogicException('Prix invalide pour l\'article : ' . json_encode($item));
            }
            $totalPrice += (float) $item['price'];
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

    #[Route('/cart/add/{id}', name: 'app_cart_add')]
    public function add(Request $request, int $id): Response
    {
        $size = $request->request->get('size');

        if (!$size) {
            $this->addFlash('error', 'Taille non spécifiée.');
            return $this->redirectToRoute('app_product', ['id' => $id]);
        }

        $cart = $request->getSession()->get('cart', []);
        $cart[] = [
            'id' => $id,
            'size' => $size,
        ];
        $request->getSession()->set('cart', $cart);

        $this->addFlash('success', 'Article ajouté au panier.');

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/create-payment-intent', name: 'app_cart_create_payment_intent', methods: ['POST'])]
    public function createPaymentIntent(Request $request, StripeService $stripeService, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            // Récupérer le panier depuis la session
            $cart = $request->getSession()->get('cart', []);

            if (empty($cart)) {
                throw new \LogicException('Votre panier est vide.');
            }

            // Mettre à jour le panier avec les prix des sweatshirts
            $updatedCart = [];
            foreach ($cart as $item) {
                $sweatshirt = $entityManager->getRepository(Sweatshirt::class)->find($item['id']);
                if (!$sweatshirt) {
                    throw new \LogicException('Article introuvable : ID ' . $item['id']);
                }
                $updatedCart[] = [
                    'id' => $item['id'],
                    'size' => $item['size'],
                    'price' => $sweatshirt->getPrice(),
                ];
            }

            // Vérifier le stock
            foreach ($updatedCart as $item) {
                $sweatshirt = $entityManager->getRepository(Sweatshirt::class)->find($item['id']);
                if (!$sweatshirt) {
                    throw new \LogicException('Article introuvable : ID ' . $item['id']);
                }
                $size = $item['size'];
                $stockFound = false;
                foreach ($sweatshirt->getStocks() as $stock) {
                    if ($stock->getSize() === $size) {
                        if ($stock->getQuantity() <= 0) {
                            throw new \LogicException('Stock insuffisant pour la taille ' . $size . ' de l\'article ID ' . $item['id']);
                        }
                        $stockFound = true;
                        break;
                    }
                }
                if (!$stockFound) {
                    throw new \LogicException('Stock invalide pour la taille ' . $size . ' de l\'article ID ' . $item['id']);
                }
            }

            // Créer un PaymentIntent
            $paymentIntent = $stripeService->createPaymentIntent($updatedCart);

            return new JsonResponse([
                'clientSecret' => $paymentIntent->client_secret,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/cart/payment', name: 'app_cart_payment')]
    public function payment(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Récupérer le panier depuis la session
        $cart = $request->getSession()->get('cart', []);

        if (empty($cart)) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('app_cart');
        }

        // Mettre à jour le panier avec les informations nécessaires pour l'affichage
        $updatedCart = [];
        $totalPrice = 0;
        foreach ($cart as $item) {
            $sweatshirt = $entityManager->getRepository(Sweatshirt::class)->find($item['id']);
            if (!$sweatshirt) {
                throw new \LogicException('Article introuvable : ID ' . $item['id']);
            }
            $updatedCart[] = [
                'id' => $item['id'],
                'name' => $sweatshirt->getName(),
                'size' => $item['size'],
                'price' => $sweatshirt->getPrice(),
            ];
            $totalPrice += $sweatshirt->getPrice();
        }

        return $this->render('cart/payment.html.twig', [
            'cart' => $updatedCart,
            'totalPrice' => $totalPrice,
            'stripePublicKey' => $this->getParameter('stripe_public_key'),
        ]);
    }

    #[Route('/cart/checkout', name: 'app_cart_checkout', methods: ['POST'], options: ['expose' => true])]
    public function checkout(Request $request, StripeService $stripeService, EntityManagerInterface $entityManager, MailerInterface $mailer): JsonResponse
    {
        try {
            // Vérifier que l'utilisateur est connecté
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw new \LogicException('Utilisateur non connecté.');
            }

            // Récupérer les données envoyées par le client
            $content = json_decode($request->getContent(), true);
            $paymentIntentId = $content['payment_intent_id'] ?? null;

            if (!$paymentIntentId) {
                throw new \LogicException('ID du PaymentIntent manquant.');
            }

            // Vérifier le statut du PaymentIntent
            $paymentIntent = $stripeService->retrievePaymentIntent($paymentIntentId);
            if ($paymentIntent->status !== 'succeeded') {
                throw new \LogicException('Le paiement n\'a pas été validé.');
            }

            // Récupérer le panier depuis la session
            $cart = $request->getSession()->get('cart', []);
            if (empty($cart)) {
                throw new \LogicException('Votre panier est vide.');
            }

            // Mettre à jour le panier avec les informations nécessaires
            $updatedCart = [];
            $totalPrice = 0;
            foreach ($cart as $item) {
                $sweatshirt = $entityManager->getRepository(Sweatshirt::class)->find($item['id']);
                if (!$sweatshirt) {
                    throw new \LogicException('Article introuvable : ID ' . $item['id']);
                }
                $updatedCart[] = [
                    'id' => $item['id'],
                    'name' => $sweatshirt->getName(),
                    'size' => $item['size'],
                    'price' => $sweatshirt->getPrice(),
                ];
                $totalPrice += $sweatshirt->getPrice();
            }

            // Vérifier le stock avant de confirmer
            foreach ($updatedCart as $item) {
                $sweatshirt = $entityManager->getRepository(Sweatshirt::class)->find($item['id']);
                if (!$sweatshirt) {
                    throw new \LogicException('Article introuvable : ID ' . $item['id']);
                }
                $size = $item['size'];
                $stockFound = false;
                foreach ($sweatshirt->getStocks() as $stock) {
                    if ($stock->getSize() === $size) {
                        if ($stock->getQuantity() <= 0) {
                            throw new \LogicException('Stock insuffisant pour la taille ' . $size . ' de l\'article ID ' . $item['id']);
                        }
                        $stock->setQuantity($stock->getQuantity() - 1);
                        $entityManager->persist($stock);
                        $stockFound = true;
                        break;
                    }
                }
                if (!$stockFound) {
                    throw new \LogicException('Stock invalide pour la taille ' . $size . ' de l\'article ID ' . $item['id']);
                }
            }
            $entityManager->flush();

            // Envoyer un e-mail de confirmation
            $email = (new TemplatedEmail())
                ->from('stubborn@blabla.com')
                ->to($user->getEmail())
                ->subject('Confirmation de votre commande - Stubborn')
                ->htmlTemplate('cart/confirmation_email.html.twig')
                ->context([
                    'cart' => $updatedCart,
                    'totalPrice' => $totalPrice,
                    'user' => $user,
                ]);

            $mailer->send($email);

            // Vider le panier
            $request->getSession()->set('cart', []);

            $this->addFlash('success', 'Votre commande a été validée et réglée avec succès !');

            // Retourner une réponse JSON avec l'URL de redirection
            return new JsonResponse([
                'success' => true,
                'redirect' => $this->generateUrl('app_cart'),
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du paiement : ' . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
                'redirect' => $this->generateUrl('app_cart'),
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}