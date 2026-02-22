<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    #[Route('/dashboard/profile', name: 'app_profile')]
    public function index(): Response
    {
        // User data
        $user = [
            'name' => 'Utilisateur',
            'email' => 'user@feelsafe.com',
            'avatar' => null,
            'location' => 'Paris, France',
            'memberSince' => 'Jan 2024',
        ];

        // Stats
        $stats = [
            ['label' => 'Jours actifs', 'value' => '45', 'icon' => 'calendar'],
            ['label' => 'Entrées journal', 'value' => '32', 'icon' => 'book'],
            ['label' => 'Minutes bien-être', 'value' => '280', 'icon' => 'clock'],
            ['label' => 'Posts forum', 'value' => '18', 'icon' => 'heart'],
        ];

        // Achievements
        $achievements = [
            ['id' => 1, 'name' => 'Premier pas', 'description' => 'Première entrée de journal', 'icon' => 'book', 'earned' => true],
            ['id' => 2, 'name' => '7 jours', 'description' => '7 jours consécutifs', 'icon' => 'flame', 'earned' => true],
            ['id' => 3, 'name' => 'Communauté', 'description' => '10 posts dans le forum', 'icon' => 'heart', 'earned' => true],
            ['id' => 4, 'name' => 'Zen Master', 'description' => '30 séances de méditation', 'icon' => 'star', 'earned' => false],
        ];

        return $this->render('dashboard/profile/index.html.twig', [
            'user' => $user,
            'stats' => $stats,
            'achievements' => $achievements,
        ]);
    }
}