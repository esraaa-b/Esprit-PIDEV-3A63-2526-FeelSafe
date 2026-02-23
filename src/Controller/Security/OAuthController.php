<?php

namespace App\Controller\Security;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class OAuthController extends AbstractController
{
    /**
     * Lien pour se connecter via Google
     */
    #[Route('/connect/google', name: 'connect_google_start')]
    public function connectGoogle(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry
            ->getClient('google')
            ->redirect(
                ['profile', 'email'],
                []
            );
    }

    /**
     * Callback Google OAuth
     */
    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectGoogleCheck(): void
    {
        // GoogleAuthenticator intercepte cette route
    }

    /**
     * Lien pour se connecter via GitHub
     */
    #[Route('/connect/github', name: 'connect_github_start')]
    public function connectGitHub(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry
            ->getClient('github')
            ->redirect(
                ['user:email'],
                []
            );
    }

    /**
     * Callback GitHub OAuth
     */
    #[Route('/connect/github/check', name: 'connect_github_check')]
    public function connectGitHubCheck(): void
    {
        // GitHubAuthenticator intercepte cette route
    }
}