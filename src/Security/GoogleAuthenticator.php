<?php

namespace App\Security;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class GoogleAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $entityManager,
        private RouterInterface $router
    ) {
    }

    public function supports(Request $request): ?bool
    {
        // Cette méthode est appelée sur chaque requête
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function() use ($accessToken, $client) {
                /** @var \League\OAuth2\Client\Provider\GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);

                $email = $googleUser->getEmail();
                $googleId = $googleUser->getId();

                // Chercher l'utilisateur par Google ID
                $user = $this->entityManager->getRepository(Utilisateur::class)
                    ->findOneBy(['googleId' => $googleId]);

                if (!$user) {
                    // Vérifier si un utilisateur avec cet email existe déjà
                    $user = $this->entityManager->getRepository(Utilisateur::class)
                        ->findOneBy(['email' => $email]);

                    if ($user) {
                        // Lier le compte Google à l'utilisateur existant
                        $user->setGoogleId($googleId);
                    } else {
                        // Créer un nouveau utilisateur
                        $user = new Utilisateur();
                        $user->setEmail($email);
                        $user->setGoogleId($googleId);
                        
                        // Récupérer nom et prénom depuis Google
                        $lastName = $googleUser->getLastName() ?? 'User';
                        $firstName = $googleUser->getFirstName() ?? 'Google';
                        
                        $user->setNom($lastName);
                        $user->setPrenom($firstName);
                        $user->setAvatar($googleUser->getAvatar());
                        $user->setRoles(['ROLE_CLIENT']);
                        $user->setStatut('actif');
                        
                        // Pas de mot de passe pour OAuth (nullable maintenant)
                        $user->setPassword(null);
                    }
                }

                // Mettre à jour l'avatar si disponible
                if ($googleUser->getAvatar()) {
                    $user->setAvatar($googleUser->getAvatar());
                }

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Rediriger vers le dashboard après connexion réussie
        return new RedirectResponse($this->router->generate('app_dashboard'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new RedirectResponse($this->router->generate('app_login', [
            'error' => $message
        ]));
    }
}