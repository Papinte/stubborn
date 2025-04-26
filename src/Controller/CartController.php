<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Sweatshirt;
use App\Service\StripeService;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

    #[Route('/cart/checkout', name: 'app_cart_checkout')]
    public function checkout(Request $request, StripeService $stripeService, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        try {
            // Vérifier que l'utilisateur est connecté
            $user = $this->getUser();
            if (!$user) {
                return $this->redirectToRoute('app_login');
            }

            // Vérifier que l'utilisateur est bien une instance de User
            if (!$user instanceof User) {
                throw new \LogicException('L\'utilisateur connecté n\'est pas une instance de User.');
            }

            // Récupérer le panier depuis la session
            $cart = $request->getSession()->get('cart', []);

            if (empty($cart)) {
                $this->addFlash('error', 'Votre panier est vide.');
                return $this->redirectToRoute('app_cart');
            }

            // Créer un PaymentIntent
            $this->logger->info('Création d\'un PaymentIntent en mode développement pour un achat-test');
            $paymentIntent = $stripeService->createPaymentIntent($cart);

            // Simuler la confirmation du paiement (en mode test)
            $this->logger->info('Confirmation du PaymentIntent en mode développement avec la méthode de paiement de test pm_card_visa');
            $stripeService->confirmPaymentIntent($paymentIntent->id);

            // Log du succès du paiement
            $this->logger->info('Achat-test réussi avec Stripe en mode développement', [
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $paymentIntent->amount / 100, // Convertir les centimes en euros
                'currency' => $paymentIntent->currency,
            ]);

            // Calculer le prix total
            $totalPrice = 0;
            foreach ($cart as $item) {
                if (!isset($item['price']) || !is_numeric($item['price'])) {
                    throw new \LogicException('Prix invalide pour l\'article : ' . json_encode($item));
                }
                $totalPrice += (float) $item['price'];
            }

            // Mettre à jour le stock
            foreach ($cart as $item) {
                $sweatshirt = $entityManager->getRepository(Sweatshirt::class)->find($item['id']);
                if ($sweatshirt) {
                    $newStock = $sweatshirt->getStock();
                    if (!is_int($newStock) && !is_numeric($newStock)) {
                        throw new \LogicException('Stock invalide pour l\'article ID ' . $item['id'] . ': ' . json_encode($newStock));
                    }
                    $newStock = (int) $newStock - 1;
                    $sweatshirt->setStock($newStock);
                    $entityManager->persist($sweatshirt);
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
                    'cart' => $cart,
                    'totalPrice' => $totalPrice,
                    'user' => $user,
                ]);

            $mailer->send($email);

            // Vider le panier
            $request->getSession()->set('cart', []);

            $this->addFlash('success', 'Votre commande a été validée et réglée avec succès !');

            return $this->redirectToRoute('app_cart');
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du paiement : ' . $e->getMessage());
            $this->addFlash('error', 'Erreur lors du paiement : ' . $e->getMessage());
            return $this->redirectToRoute('app_cart');
        }
    }
}