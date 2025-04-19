<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class TestMailController extends AbstractController
{
    #[Route('/test-mail', name: 'test_mail')]
    public function index(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->from('stubborn@blabla.com')
            ->to('test@example.com')
            ->subject('Test Email from Stubborn')
            ->text('Ceci est un test !');

        $mailer->send($email);

        return new Response('E-mail envoyé ! Vérifiez Mailtrap.');
    }
}