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
    }

    public function testCheckoutSuccess(): void
    {
        // Récupérer un utilisateur existant dans stubborn_test
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'test@example.com']);
        $this->assertNotNull($user, 'L’utilisateur "test@example.com" devrait exister dans la base stubborn_test.');

        // Récupérer un sweatshirt existant dans stubborn_test
        $sweatshirt = $this->entityManager->getRepository(Sweatshirt::class)->findOneBy(['name' => 'Blackbelt']);
        $this->assertNotNull($sweatshirt, 'Le sweatshirt "Blackbelt" devrait exister dans la base stubborn_test.');

        // Vérifier que le stock pour la taille S existe et est suffisant
        $stock = $this->entityManager->getRepository(Stock::class)->findOneBy(['sweatshirt' => $sweatshirt, 'size' => 'S']);
        $this->assertNotNull($stock, 'Le stock pour la taille S du sweatshirt "Blackbelt" devrait exister dans la base.');
        $this->assertGreaterThan(0, $stock->getQuantity(), 'Le stock pour la taille S du sweatshirt "Blackbelt" devrait être supérieur à 0.');

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

        $response = $controller->checkout($request, $this->stripeService, $this->entityManager, $this->mailer);

        // Vérifier que la redirection est correcte
        $this->assertEquals(302, $response->getStatusCode(), 'La redirection devrait retourner un code 302.');
        $this->assertEquals('/cart', $response->headers->get('location'), 'La redirection devrait aller vers /cart.');

        // Vérifier les messages flash pour déboguer
        $flashMessages = $this->session->getFlashBag()->all();
        if (!isset($flashMessages['success'])) {
            $this->fail('Échec du paiement : aucun message de succès. Flash messages : ' . json_encode($flashMessages));
        }

        $this->assertArrayHasKey('success', $flashMessages, 'Un message de succès devrait être présent.');
        $this->assertEquals('Votre commande a été validée et réglée avec succès !', $flashMessages['success'][0], 'Le message de succès devrait être correct.');

        // Vérifier que le panier a été vidé
        $cartAfterCheckout = $this->session->get('cart', []);
        $this->assertEmpty($cartAfterCheckout, 'Le panier devrait être vidé après le règlement.');

        // Vérifier que le stock a été mis à jour
        $updatedStock = $this->entityManager->getRepository(Stock::class)->findOneBy(['sweatshirt' => $sweatshirt, 'size' => 'S']);
        $this->assertEquals($stock->getQuantity() - 1, $updatedStock->getQuantity(), 'Le stock devrait avoir été décrémenté de 1.');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}