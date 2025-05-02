<?php

namespace App\Tests;

use App\Controller\CartController;
use App\Entity\Sweatshirt;
use App\Entity\Stock;
use App\Entity\User;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Mailer\MailerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class OrderTest extends KernelTestCase
{
    private $entityManager;
    private $session;
    private $stripeService;
    private $mailer;
    private $logger;
    private $requestStack;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->session = new Session(new MockArraySessionStorage());
        $this->stripeService = $this->createMock(StripeService::class);
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->requestStack = self::getContainer()->get('request_stack');
        $request = new Request();
        $request->setSession($this->session);
        $this->requestStack->push($request);

        // Réinitialiser le stock pour les tests
        $this->resetStockForTests();
    }

    private function resetStockForTests(): void
    {
        // Récupérer le sweatshirt "Blackbelt"
        $sweatshirt = $this->entityManager->getRepository(Sweatshirt::class)->findOneBy(['name' => 'Blackbelt']);
        if ($sweatshirt) {
            // Récupérer le stock pour la taille S
            $stock = $this->entityManager->getRepository(Stock::class)->findOneBy(['sweatshirt' => $sweatshirt, 'size' => 'S']);
            if ($stock) {
                $stock->setQuantity(2); // Réinitialiser le stock à 2
                $this->entityManager->persist($stock);
                $this->entityManager->flush();
            }
        }
    }

    public function testCheckoutSuccess(): void
    {
        // Récupérer un utilisateur existant dans stubborn_test
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'test@example.com']);
        $this->assertNotNull($user, 'L’utilisateur "test@example.com" devrait exister dans la base stubborn_test.');
    
        // Récupérer un sweatshirt existant dans stubborn_test
        $sweatshirt = $this->entityManager->getRepository(Sweatshirt::class)->findOneBy(['name' => 'Blackbelt']);
        $this->assertNotNull($sweatshirt, 'Le sweatshirt "Blackbelt" devrait exister dans la base stubborn_test.');
    
        // Récupérer le stock pour la taille S et vérifier sa quantité
        $stock = $this->entityManager->getRepository(Stock::class)->findOneBy(['sweatshirt' => $sweatshirt, 'size' => 'S']);
        $this->assertNotNull($stock, 'Le stock pour la taille S du sweatshirt "Blackbelt" devrait exister dans la base.');
        $initialStockQuantity = $stock->getQuantity();
        $this->assertEquals(2, $initialStockQuantity, 'Le stock devrait être réinitialisé à 2.');
    
        // Simuler un panier dans la session
        $cart = [
            [
                'id' => $sweatshirt->getId(),
                'name' => $sweatshirt->getName(),
                'size' => 'S',
                'price' => $sweatshirt->getPrice(),
            ]
        ];
        $this->session->set('cart', $cart);
    
        // Simuler un PaymentIntent Stripe
        $paymentIntent = new \Stripe\PaymentIntent('pi_123', ['client_secret' => 'fake-secret', 'status' => 'requires_confirmation']);
        $this->stripeService->method('createPaymentIntent')
            ->willReturn($paymentIntent);
    
        // Simuler confirmPaymentIntent sans exception (void)
        $this->stripeService->method('confirmPaymentIntent')
            ->with($this->equalTo('pi_123'))
            ->will($this->returnCallback(function () {
                // Ne rien retourner (void)
            }));
    
        // Mocker retrievePaymentIntent pour simuler un statut succeeded
        $paymentIntentAfterConfirm = new \Stripe\PaymentIntent('pi_123', ['client_secret' => 'fake-secret']);
        $paymentIntentAfterConfirm->status = 'succeeded';
        $this->stripeService->method('retrievePaymentIntent')
            ->with($this->equalTo('pi_123'))
            ->willReturn($paymentIntentAfterConfirm);
    
        // Simuler une requête POST pour checkout avec payment_intent_id
        $request = new Request([], [], [], [], [], [], json_encode([
            'payment_intent_id' => 'pi_123',
        ]));
        $request->setMethod('POST');
        $request->setSession($this->session);
    
        // Simuler l'envoi d'email
        $this->mailer->expects($this->once())
            ->method('send');
    
        // Créer une instance de CartController
        $controller = new CartController($this->logger);
        $controller->setContainer(self::getContainer());
    
        // Simuler l'utilisateur connecté
        $tokenStorage = self::getContainer()->get('security.token_storage');
        $token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken($user, 'main', $user->getRoles());
        $tokenStorage->setToken($token);
    
        // Appeler checkout et gérer les exceptions
        try {
            $response = $controller->checkout($request, $this->stripeService, $this->entityManager, $this->mailer);
    
            // Vérifier que la réponse est une JsonResponse
            $this->assertInstanceOf(JsonResponse::class, $response);
            $this->assertEquals(200, $response->getStatusCode(), 'La requête devrait réussir avec un code 200.');
    
            // Vérifier le contenu de la réponse JSON
            $responseData = json_decode($response->getContent(), true);
            $this->assertTrue($responseData['success'], 'La réponse JSON devrait indiquer un succès.');
            $this->assertEquals('/cart', $responseData['redirect'], 'La redirection devrait pointer vers /cart.');
    
            // Vérifier que le panier a été vidé
            $cartAfterCheckout = $this->session->get('cart', []);
            $this->assertEmpty($cartAfterCheckout, 'Le panier devrait être vidé après le règlement.');
    
            // Recharger l'entité stock pour s'assurer que les changements sont bien appliqués
            $this->entityManager->clear(); // Nettoyer le cache de l'EntityManager
            $updatedStock = $this->entityManager->getRepository(Stock::class)->findOneBy(['sweatshirt' => $sweatshirt, 'size' => 'S']);
            $this->assertEquals($initialStockQuantity - 1, $updatedStock->getQuantity(), 'Le stock devrait avoir été décrémenté de 1.');
    
            // Simuler une requête GET à /cart pour vérifier le rendu
            $cartRequest = new Request();
            $cartRequest->setSession($this->session);
            $cartResponse = $controller->index($cartRequest);
    
            // Vérifier que la réponse est une Response (HTML)
            $this->assertInstanceOf(Response::class, $cartResponse);
            $this->assertEquals(200, $cartResponse->getStatusCode());
    
            // Vérifier que le message de succès est présent dans le contenu HTML
            $content = $cartResponse->getContent();
            $this->assertStringContainsString(
                'Votre commande a été validée et réglée avec succès !',
                $content,
                'Le message de succès devrait être présent dans la page.'
            );
        } catch (\Exception $e) {
            $this->fail('Exception levée dans CartController::checkout : ' . $e->getMessage());
        }
    }
}