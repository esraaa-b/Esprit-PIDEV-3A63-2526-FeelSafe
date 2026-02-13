<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends AbstractController
{
    #[Route('/dashboard/security', name: 'app_security')]
    public function index(): Response
    {
        // User data
        $user = [
            'name' => 'Utilisateur',
            'email' => 'user@feelsafe.com',
            'avatar' => null,
            'joined' => 'Janvier 2024',
        ];

        // Active sessions
        $sessions = [
            ['device' => 'Chrome - Windows', 'location' => 'Paris, France', 'lastActive' => 'Maintenant', 'current' => true],
            ['device' => 'Safari - iPhone', 'location' => 'Lyon, France', 'lastActive' => 'Il y a 2h', 'current' => false],
            ['device' => 'Firefox - MacOS', 'location' => 'Paris, France', 'lastActive' => 'Hier', 'current' => false],
        ];

        return $this->render('dashboard/security/index.html.twig', [
            'user' => $user,
            'sessions' => $sessions,
        ]);
    }
}