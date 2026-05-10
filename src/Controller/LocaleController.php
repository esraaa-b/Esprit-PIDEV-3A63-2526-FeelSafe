<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * ══════════════════════════════════════════════════════════════════
 *  LocaleController
 *  ──────────────────────────────────────────────────────────────
 *  Gère le changement de langue (FR ↔ AR) dans l'application.
 *
 *  Usage dans Twig :
 *    <a href="{{ path('app_locale_switch', {locale: 'ar'}) }}">AR</a>
 *    <a href="{{ path('app_locale_switch', {locale: 'fr'}) }}">FR</a>
 *
 *  Ou avec le bouton toggle dans auth_base.html.twig :
 *    <a href="{{ path('app_locale_switch', {locale: app.request.locale == 'ar' ? 'fr' : 'ar'}) }}">
 *      {{ app.request.locale == 'ar' ? '🌐 FR' : '🌐 AR' }}
 *    </a>
 * ══════════════════════════════════════════════════════════════════
 */
class LocaleController extends AbstractController
{
    /**
     * Change la langue et redirige vers la page précédente.
     */
    #[Route('/locale/switch/{locale}', name: 'app_locale_switch', requirements: ['locale' => 'fr|ar'])]
    public function switch(string $locale, Request $request): Response
    {
        // Stocker la langue dans la session
        $request->getSession()->set('_locale', $locale);

        // Rediriger vers la page précédente (ou accueil si pas de referer)
        $referer = $request->headers->get('referer', $this->generateUrl('app_home'));

        return $this->redirect($referer);
    }
}
