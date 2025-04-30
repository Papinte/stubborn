<?php

namespace App\Controller;

use App\Entity\Sweatshirt;
use App\Entity\Stock;
use App\Form\SweatshirtInlineType;
use App\Form\SweatshirtType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Log\LoggerInterface;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/', name: 'app_admin', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $sweatshirts = $entityManager->getRepository(Sweatshirt::class)->findAll();

        // Créer un formulaire pour chaque sweat-shirt
        $forms = [];
        $submittedSweatshirtId = $request->request->get('sweatshirt_id');
        $this->logger->info('Sweatshirt ID soumis: ' . ($submittedSweatshirtId ?: 'aucun'));

        foreach ($sweatshirts as $sweatshirt) {
            $form = $this->createForm(SweatshirtInlineType::class, $sweatshirt, [
                'csrf_field_name' => '_token',
                'csrf_token_id' => 'sweatshirt_inline_' . $sweatshirt->getId(),
            ]);
            if ($submittedSweatshirtId == $sweatshirt->getId()) {
                $this->logger->info('Traitement du formulaire pour le sweat-shirt ID: ' . $sweatshirt->getId());
                $form->handleRequest($request);
            }

            if ($submittedSweatshirtId == $sweatshirt->getId() && $form->isSubmitted()) {
                $this->logger->info('Formulaire soumis pour le sweat-shirt ID: ' . $sweatshirt->getId());
                $this->logger->info('Données soumises: ' . json_encode($request->request->all()));

                if ($form->isValid()) {
                    // Gérer l’upload de l’image
                    $imageFile = $form->get('image')->getData();
                    if ($imageFile) {
                        $this->logger->info('Image uploadée: ' . $imageFile->getClientOriginalName());
                        $newFilename = uniqid().'.'.$imageFile->guessExtension();
                        $imageFile->move(
                            $this->getParameter('kernel.project_dir').'/public/images',
                            $newFilename
                        );
                        $sweatshirt->setImage($newFilename);
                    }

                    // Mettre à jour les champs name et price
                    $formData = $request->request->all();
                    $submittedData = $formData['sweatshirt_inline'] ?? [];
                    if (!empty($submittedData['name'])) {
                        $sweatshirt->setName($submittedData['name']);
                    }
                    if (!empty($submittedData['price']) && is_numeric($submittedData['price'])) {
                        $sweatshirt->setPrice((float)str_replace(',', '.', $submittedData['price']));
                    }

                    // Mettre à jour les stocks
                    $stocksData = $submittedData['stocks'] ?? [];
                    $this->logger->info('Données des stocks: ' . json_encode($stocksData));
                    foreach ($sweatshirt->getStocks() as $stock) {
                        $size = $stock->getSize();
                        if (isset($stocksData[$size]) && is_numeric($stocksData[$size]) && (int)$stocksData[$size] >= 0) {
                            $stock->setQuantity((int)$stocksData[$size]);
                            $entityManager->persist($stock);
                            $this->logger->info("Stock mis à jour pour la taille $size: " . $stocksData[$size]);
                        }
                    }

                    $entityManager->persist($sweatshirt);
                    $entityManager->flush();
                    $this->logger->info('Sweat-shirt sauvegardé: ' . json_encode([
                        'id' => $sweatshirt->getId(),
                        'name' => $sweatshirt->getName(),
                        'price' => $sweatshirt->getPrice(),
                        'isFeatured' => $sweatshirt->isFeatured(),
                        'image' => $sweatshirt->getImage(),
                        'stocks' => array_map(fn($s) => [$s->getSize() => $s->getQuantity()], $sweatshirt->getStocks()->toArray()),
                    ]));

                    $this->addFlash('success', 'Sweat-shirt modifié avec succès !');
                    return $this->redirectToRoute('app_admin');
                } else {
                    $this->logger->error('Formulaire invalide pour le sweat-shirt ID: ' . $sweatshirt->getId());
                    $this->logger->error('Erreurs: ' . $form->getErrors(true, true)->__toString());
                    $this->addFlash('error', 'Erreur lors de la modification du sweat-shirt ID ' . $sweatshirt->getId() . ': ' . $form->getErrors(true, true)->__toString());
                }
            }

            $forms[$sweatshirt->getId()] = $form->createView();
        }

        // Formulaire pour ajouter un nouveau sweat-shirt
        $newSweatshirt = new Sweatshirt();
        $newForm = $this->createForm(SweatshirtType::class, $newSweatshirt);
        $newForm->handleRequest($request);

        if ($newForm->isSubmitted()) {
            $this->logger->info('Formulaire d’ajout soumis: ' . json_encode($request->request->all()));
            if ($newForm->isValid()) {
                // Gérer l’upload de l’image
                $imageFile = $newForm->get('image')->getData();
                if ($imageFile) {
                    $this->logger->info('Image uploadée (ajout): ' . $imageFile->getClientOriginalName());
                    $newFilename = uniqid().'.'.$imageFile->guessExtension();
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir').'/public/images',
                        $newFilename
                    );
                    $newSweatshirt->setImage($newFilename);
                }

                // Ajouter des stocks pour chaque taille
                $sizes = ['XS', 'S', 'M', 'L', 'XL'];
                foreach ($sizes as $size) {
                    $stock = new Stock();
                    $stock->setSize($size);
                    $stock->setQuantity(2);
                    $newSweatshirt->addStock($stock);
                }

                $entityManager->persist($newSweatshirt);
                $entityManager->flush();

                $this->addFlash('success', 'Sweat-shirt ajouté avec succès !');
                return $this->redirectToRoute('app_admin');
            } else {
                $this->logger->error('Formulaire d’ajout invalide: ' . $newForm->getErrors(true, true)->__toString());
                $this->addFlash('error', 'Erreur lors de l’ajout du sweat-shirt: ' . $newForm->getErrors(true, true)->__toString());
            }
        }

        return $this->render('admin/index.html.twig', [
            'sweatshirts' => $sweatshirts,
            'forms' => $forms,
            'new_form' => $newForm->createView(),
        ]);
    }

    #[Route('/delete/{id}', name: 'app_admin_delete', methods: ['POST'])]
    public function delete(Request $request, Sweatshirt $sweatshirt, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$sweatshirt->getId(), $request->request->get('_token'))) {
            $entityManager->remove($sweatshirt);
            $entityManager->flush();

            $this->addFlash('success', 'Sweat-shirt supprimé avec succès !');
        } else {
            $this->addFlash('error', 'Erreur lors de la suppression: jeton CSRF invalide.');
        }

        return $this->redirectToRoute('app_admin');
    }
}