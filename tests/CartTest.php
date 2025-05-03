<?php

namespace App\Tests;

use App\Controller\ProductController;
use App\Entity\Sweatshirt;
use App\Entity\Stock;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CartTest extends KernelTestCase
{
    private $entityManager;
    private $session;
    private $requestStack;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        // Créer une session simulée
        $this->session = new Session(new MockArraySessionStorage());

        // Récupérer le RequestStack existant depuis le conteneur
        $this->requestStack = self::getContainer()->get('request_stack');
        $request = new Request();
        $request->setSession($this->session);
        $this->requestStack->push($request);

        // Mocker le CsrfTokenManager pour accepter notre jeton CSRF simulé
        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfTokenManager->method('isTokenValid')
            ->willReturnCallback(function (CsrfToken $token) {
                return $token->getValue() === 'fake-csrf-token';
            });
        self::getContainer()->set('security.csrf.token_manager', $csrfTokenManager);
    }

    public function testAddToCart(): void
    {
        // Récupérer un sweatshirt existant dans stubborn_test
        $sweatshirt = $this->entityManager->getRepository(Sweatshirt::class)->findOneBy(['name' => 'Blackbelt']);
        $this->assertNotNull($sweatshirt, 'Le sweatshirt "Blackbelt" devrait exister dans la base stubborn_test.');

        // Vérifier que le stock pour la taille S existe et est suffisant
        $stock = $this->entityManager->getRepository(Stock::class)->findOneBy(['sweatshirt' => $sweatshirt, 'size' => 'S']);
        $this->assertNotNull($stock, 'Le stock pour la taille S du sweatshirt "Blackbelt" devrait exister dans la base.');
        $this->assertGreaterThan(0, $stock->getQuantity(), 'Le stock pour la taille S du sweatshirt "Blackbelt" devrait être supérieur à 0.');

        // Simuler une requête POST pour ajouter au panier
        $request = new Request([], [
            'form' => [
                'size' => 'S',
                '_token' => 'fake-csrf-token', // Simuler un jeton CSRF
            ],
        ]);
        $request->setMethod('POST');
        $request->setSession($this->session);

        // Créer une instance de ProductController
        $controller = new ProductController();
        $controller->setContainer(self::getContainer());
        $response = $controller->show($request, $sweatshirt);

        // Vérifier que la redirection est correcte
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/cart', $response->headers->get('location'));

        // Vérifier que le produit a bien été ajouté au panier
        $cartItems = $this->session->get('cart', []);
        $this->assertCount(1, $cartItems, 'Le panier devrait contenir exactement 1 article.');
        $this->assertEquals($sweatshirt->getId(), $cartItems[0]['id'], 'L’ID du produit dans le panier devrait correspondre.');
        $this->assertEquals('S', $cartItems[0]['size'], 'La taille dans le panier devrait être S.');
        $this->assertEquals($sweatshirt->getPrice(), $cartItems[0]['price'], 'Le prix dans le panier devrait correspondre.');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}