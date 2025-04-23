<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    public function __construct(private EmailVerifier $emailVerifier, private Security $security)
    {
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            // generate a signed url and email it to the user
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from('stubborn@blabla.com')
                    ->to($user->getEmail())
                    ->subject('Confirmez votre inscription à Stubborn')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );

            // Rediriger vers /login après inscription (sans connecter l'utilisateur)
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, EntityManagerInterface $entityManager): Response
    {
        $id = $request->query->get('id');
    
        if (null === $id) {
            $this->addFlash('verify_email_error', 'ID utilisateur manquant dans l\'URL.');
            return $this->redirectToRoute('app_register');
        }
    
        $user = $entityManager->getRepository(User::class)->find($id);
    
        if (null === $user) {
            $this->addFlash('verify_email_error', 'Utilisateur non trouvé pour l\'ID ' . $id);
            return $this->redirectToRoute('app_register');
        }
    
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', 'Erreur lors de la vérification : ' . $exception->getReason());
            return $this->redirectToRoute('app_register');
        } catch (\Exception $e) {
            $this->addFlash('verify_email_error', 'Erreur inattendue : ' . $e->getMessage());
            return $this->redirectToRoute('app_register');
        }
    
        $this->addFlash('success', 'Votre adresse e-mail a été vérifiée.');
    
        $this->security->login($user, 'form_login', 'main');
    
        return $this->redirectToRoute('app_home');
    }
}