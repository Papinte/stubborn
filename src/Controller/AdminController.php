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
        foreach ($sweatshirts as $sweatshirt) {
            $form = $this->createForm(SweatshirtInlineType::class, $sweatshirt);
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
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

                    // Mettre à jour les stocks
                    $formData = $request->request->get('sweatshirt_inline');
                    $this->logger->info('Données des stocks: ' . json_encode($formData['stocks'] ?? []));
                    if (isset($formData['stocks'])) {
                        $stocksData = $formData['stocks'];
                        foreach ($sweatshirt->getStocks() as $stock) {
                            $size = $stock->getSize();
                            if (isset($stocksData[$size]) && is_numeric($stocksData[$size])) {
                                $stock->setQuantity((int)$stocksData[$size]);
                                $this->logger->info("Stock mis à jour pour la taille $size: " . $stocksData[$size]);
                            }
                        }
                    }

                    $entityManager->persist($sweatshirt);
                    $entityManager->flush();
                    $this->logger->info('Sweat-shirt sauvegardé: ' . json_encode([
                        'id' => $sweatshirt->getId(),
                        'name' => $sweatshirt->getName(),
                        'price' => $sweatshirt->getPrice(),
                        'isFeatured' => $sweatshirt->getIsFeatured(),
                        'image' => $sweatshirt->getImage(),
                    ]));

                    $this->addFlash('success', 'Sweat-shirt modifié avec succès !');
                    return $this->redirectToRoute('app_admin');
                } else {
                    $this->logger->error('Formulaire invalide: ' . json_encode($form->getErrors(true)));
                    $this->addFlash('error', 'Erreur lors de la modification du sweat-shirt.');
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
                    $stock->setQuantity(2); // Stock initial par défaut
                    $newSweatshirt->addStock($stock);
                }

                $entityManager->persist($newSweatshirt);
                $entityManager->flush();

                $this->addFlash('success', 'Sweat-shirt ajouté avec succès !');
                return $this->redirectToRoute('app_admin');
            } else {
                $this->logger->error('Formulaire d’ajout invalide: ' . json_encode($newForm->getErrors(true)));
                $this->addFlash('error', 'Erreur lors de l’ajout du sweat-shirt.');
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
        }

        return $this->redirectToRoute('app_admin');
    }
}