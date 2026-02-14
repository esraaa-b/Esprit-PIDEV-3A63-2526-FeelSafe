<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginRedirectSubscriber implements EventSubscriberInterface
{
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        
        // Vérifier les rôles et rediriger en conséquence
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $targetUrl = $this->urlGenerator->generate('admin_dashboard');
        } elseif (in_array('ROLE_PROFESSIONNEL', $user->getRoles())) {
            $targetUrl = $this->urlGenerator->generate('professionnel_dashboard');
        } elseif (in_array('ROLE_CLIENT', $user->getRoles())) {
            $targetUrl = $this->urlGenerator->generate('client_dashboard');
        } else {
            // Par défaut
            $targetUrl = $this->urlGenerator->generate('client_dashboard');
        }

        $response = new RedirectResponse($targetUrl);
        $event->setResponse($response);
    }
}