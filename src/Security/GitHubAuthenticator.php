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

class GitHubAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $entityManager,
        private RouterInterface $router
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_github_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('github');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function() use ($accessToken, $client) {
                /** @var \League\OAuth2\Client\Provider\GithubResourceOwner $githubUser */
                $githubUser = $client->fetchUserFromToken($accessToken);

                $email = $githubUser->getEmail();
                $githubId = $githubUser->getId();

                // Si pas d'email public sur GitHub, on ne peut pas créer le compte
                if (!$email) {
                    throw new AuthenticationException('Votre email GitHub doit être public pour vous connecter.');
                }

                // Chercher l'utilisateur par GitHub ID
                $user = $this->entityManager->getRepository(Utilisateur::class)
                    ->findOneBy(['githubId' => $githubId]);

                if (!$user) {
                    // Vérifier si un utilisateur avec cet email existe déjà
                    $user = $this->entityManager->getRepository(Utilisateur::class)
                        ->findOneBy(['email' => $email]);

                    if ($user) {
                        // Lier le compte GitHub à l'utilisateur existant
                        $user->setGithubId($githubId);
                    } else {
                        // Créer un nouveau utilisateur
                        $user = new Utilisateur();
                        $user->setEmail($email);
                        $user->setGithubId($githubId);
                        
                        // GitHub retourne parfois juste un "name" ou "login"
                        $name = $githubUser->getName() ?? $githubUser->getNickname() ?? 'GitHub User';
                        $nameParts = explode(' ', $name, 2);
                        
                        $user->setPrenom($nameParts[0] ?? 'GitHub');
                        $user->setNom($nameParts[1] ?? 'User');
                        
                        // Avatar GitHub
                        $avatarUrl = $githubUser->toArray()['avatar_url'] ?? null;
                        if ($avatarUrl) {
                            $user->setAvatar($avatarUrl);
                        }
                        
                        $user->setRoles(['ROLE_CLIENT']);
                        $user->setStatut('actif');
                        $user->setPassword(null);
                    }
                }

                // Mettre à jour l'avatar si disponible
                $avatarUrl = $githubUser->toArray()['avatar_url'] ?? null;
                if ($avatarUrl) {
                    $user->setAvatar($avatarUrl);
                }

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
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