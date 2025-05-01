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
        // Utiliser une session simulée avec un stockage en mémoire
        $this->session = new Session(new MockArraySessionStorage());
        $this->stripeService = $this->createMock(StripeService::class);
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // Récupérer le RequestStack existant depuis le conteneur
        $this->requestStack = self::getContainer()->get('request_stack');
        $request = new Request();
        $request->setSession($this->session);
        $this->requestStack->push($request);
    }

    public function testCheckoutSuccess(): void
    {
        // Récupérer un utilisateur existant dans stubborn
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'test@example.com']);
        $this->assertNotNull($user, 'L’utilisateur "test@example.com" devrait exister dans la base stubborn.');

        // Récupérer un sweatshirt existant dans stubborn
        $sweatshirt = $this->entityManager->getRepository(Sweatshirt::class)->findOneBy(['name' => 'Blackbelt']);
        $this->assertNotNull($sweatshirt, 'Le sweatshirt "Blackbelt" devrait exister dans la base stubborn.');

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

        // Simuler un PaymentIntent Stripe réussi
        $paymentIntentMock = new \stdClass();
        $paymentIntentMock->status = 'succeeded';
        $paymentIntent = new \Stripe\PaymentIntent('pi_123', ['client_secret' => 'fake-secret']);
        $this->stripeService->method('createPaymentIntent')
            ->willReturn($paymentIntent);
        $this->stripeService->method('confirmPaymentIntent')
            ->willReturn(null);

        // Simuler une requête POST pour checkout
        $request = new Request([], [], [], [], [], [], json_encode([
            'payment_intent_id' => 'pi_123',
        ]));
        $request->setMethod('POST');
        $request->setSession($this->session);

        // Créer une instance de CartController
        $controller = new CartController($this->logger);
        $controller->setContainer(self::getContainer());

        // Simuler l'utilisateur connecté
        $tokenStorage = self::getContainer()->get('security.token_storage');
        $token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken($user, 'main', $user->getRoles());
        $tokenStorage->setToken($token);

        $response = $controller->checkout($request, $this->stripeService, $this->entityManager, $this->mailer);

        // Vérifier que la redirection est correcte
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/cart', $response->headers->get('location'));

        // Vérifier que le panier a été vidé
        $this->assertEmpty($this->session->get('cart'), 'Le panier devrait être vidé après le règlement.');

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