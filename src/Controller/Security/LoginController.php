<?php

namespace App\Controller\Security;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function index(AuthenticationUtils $authenticationUtils): Response
    {
        // Si l'utilisateur est déjà connecté, le rediriger vers son dashboard
        if ($this->getUser()) {
            $user = $this->getUser();

            // Si l'utilisateur a le rôle admin, aller vers la page admin principale
            if (method_exists($user, 'getRoles') && in_array('ROLE_ADMIN', $user->getRoles(), true)) {
                return $this->redirectToRoute('admin_home');
            }

            // Sinon rediriger vers le dashboard utilisateur
            return $this->redirectToRoute('app_dashboard');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user (if any)
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('auth/login/index.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Cette méthode peut rester vide - elle sera interceptée par la clé logout de votre firewall
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}