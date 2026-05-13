<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * ══════════════════════════════════════════════════════════════════
 *  LocaleSubscriber
 *  ──────────────────────────────────────────────────────────────
 *  Applique automatiquement la langue stockée en session à chaque
 *  requête Symfony.
 *
 *  Enregistrement automatique via autoconfigure: true dans services.yaml
 * ══════════════════════════════════════════════════════════════════
 */
class LocaleSubscriber implements EventSubscriberInterface
{
    private string $defaultLocale;

    public function __construct(string $defaultLocale = 'fr')
    {
        $this->defaultLocale = $defaultLocale;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$event->isMainRequest()) {
            return;
        }

        // Lire la langue depuis la session (stockée par LocaleController)
        $locale = $request->getSession()->get('_locale', $this->defaultLocale);

        // Appliquer la langue à la requête Symfony
        $request->setLocale($locale);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Priorité haute pour appliquer la locale avant les autres listeners
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}
