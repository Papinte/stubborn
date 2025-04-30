<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class CustomFormLoginAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    private $urlGenerator;
    private $entityManager;

    public function __construct(UrlGeneratorInterface $urlGenerator, EntityManagerInterface $entityManager)
    {
        $this->urlGenerator = $urlGenerator;
        $this->entityManager = $entityManager;
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate('app_login');
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('_username', '');
        $password = $request->request->get('_password', '');
        $csrfToken = $request->request->get('_csrf_token', '');

        error_log('CustomFormLoginAuthenticator: Authenticating user ' . $email);

        return new Passport(
            new UserBadge($email, function ($userIdentifier) {
                // Charger l'utilisateur via le repository
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $userIdentifier]);
                if (!$user instanceof User) {
                    error_log('CustomFormLoginAuthenticator: User not found for email ' . $userIdentifier);
                    throw new AuthenticationException('Utilisateur non trouvé.');
                }

                error_log('CustomFormLoginAuthenticator: Loaded user ' . $userIdentifier . ', isVerified=' . ($user->isVerified() ? 'true' : 'false'));

                // Vérifier si l'utilisateur est vérifié
                if (!$user->isVerified()) {
                    error_log('CustomFormLoginAuthenticator: User not verified, throwing exception');
                    throw new AuthenticationException('Votre compte n’est pas encore vérifié. Veuillez vérifier votre adresse e-mail pour activer votre compte.');
                }

                return $user;
            }),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        error_log('CustomFormLoginAuthenticator: User class=' . (is_object($user) ? get_class($user) : 'null'));

        if (!$user instanceof User) {
            error_log('CustomFormLoginAuthenticator: User is not an instance of App\Entity\User');
            throw new AuthenticationException('Utilisateur invalide.');
        }

        error_log('CustomFormLoginAuthenticator: User verified, redirecting to app_home');
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            error_log('CustomFormLoginAuthenticator: Redirecting to target path: ' . $targetPath);
            return new RedirectResponse($targetPath);
        }

        error_log('CustomFormLoginAuthenticator: Redirecting to app_home');
        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }
}