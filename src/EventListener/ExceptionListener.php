<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ExceptionListener
{
    private $urlGenerator;
    private $tokenStorage;

    public function __construct(UrlGeneratorInterface $urlGenerator, TokenStorageInterface $tokenStorage)
    {
        $this->urlGenerator = $urlGenerator;
        $this->tokenStorage = $tokenStorage;
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Gérer les erreurs d’accès interdit
        if ($exception instanceof AccessDeniedHttpException) {
            // Si l’utilisateur n’est pas connecté (pas de token), rediriger vers le login
            if (!$this->tokenStorage->getToken() || !$this->tokenStorage->getToken()->getUser()) {
                $response = new RedirectResponse($this->urlGenerator->generate('app_login'));
                $event->setResponse($response);
                return;
            }

            // Si l’utilisateur est connecté mais n’a pas le rôle admin, ajouter un message flash
            $request = $event->getRequest();
            $session = $request->getSession();
            $session->set('_flash', array_merge($session->get('_flash', []), ['error' => ['Vous n’avez pas les autorisations nécessaires pour accéder à cette page.']]));

            $response = new RedirectResponse($this->urlGenerator->generate('app_home'));
            $event->setResponse($response);
        }
    }
}