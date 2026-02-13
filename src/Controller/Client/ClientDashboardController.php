<?php

namespace App\Controller\Client;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ClientDashboardController extends BaseDashboardController
{
    #[Route('/dashboard', name: 'client_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        // Calculate greeting based on time of day
        $hour = (int) date('G');
        if ($hour < 12) {
            $greeting = 'Bonjour';
        } elseif ($hour < 18) {
            $greeting = 'Bon après-midi';
        } else {
            $greeting = 'Bonsoir';
        }

        // Get user's name
        $user = $this->getUser();
        $userName = $user->getPrenom();

        // Mood history data
        $moodHistory = [
            ['day' => 'Lun', 'mood' => 'happy', 'value' => 80],
            ['day' => 'Mar', 'mood' => 'neutral', 'value' => 60],
            ['day' => 'Mer', 'mood' => 'happy', 'value' => 85],
            ['day' => 'Jeu', 'mood' => 'sad', 'value' => 40],
            ['day' => 'Ven', 'mood' => 'neutral', 'value' => 55],
            ['day' => 'Sam', 'mood' => 'happy', 'value' => 90],
            ['day' => 'Dim', 'mood' => 'happy', 'value' => 75],
        ];

        // Mood options for selection
        $moodOptions = [
            ['id' => 'great', 'label' => 'Très bien', 'icon' => '😊', 'color' => 'bg-primary'],
            ['id' => 'good', 'label' => 'Bien', 'icon' => '🙂', 'color' => 'bg-chart-4'],
            ['id' => 'okay', 'label' => 'Neutre', 'icon' => '😐', 'color' => 'bg-chart-2'],
            ['id' => 'bad', 'label' => 'Pas bien', 'icon' => '😔', 'color' => 'bg-chart-5'],
            ['id' => 'awful', 'label' => 'Très mal', 'icon' => '😢', 'color' => 'bg-destructive'],
        ];

        // Modules data
        $modules = [
            [
                'name' => 'Sécurité & Profil',
                'description' => 'Gérez votre compte et vos paramètres de confidentialité',
                'href' => '/dashboard/security',
                'icon' => 'shield',
                'color' => 'bg-chart-3/10 text-chart-3',
                'image' => '/images/hero-wellness.jpg'
            ],
            [
                'name' => 'Journal Émotionnel',
                'description' => 'Exprimez vos émotions et suivez votre ressenti',
                'href' => '/dashboard/journal',
                'icon' => 'book',
                'color' => 'bg-chart-2/10 text-chart-2',
                'image' => '/images/journal.jpg'
            ],
            [
                'name' => 'Accompagnement',
                'description' => 'Support intelligent et rendez-vous professionnels',
                'href' => '/dashboard/support',
                'icon' => 'heart',
                'color' => 'bg-chart-4/10 text-chart-4',
                'image' => '/images/support.jpg'
            ],
            [
                'name' => 'Bien-être',
                'description' => 'Exercices de respiration et activités douces',
                'href' => '/dashboard/wellness',
                'icon' => 'sun',
                'color' => 'bg-primary/10 text-primary',
                'image' => '/images/wellness-activity.jpg'
            ],
            [
                'name' => 'Urgence',
                'description' => 'Signalez une situation urgente et obtenez de l\'aide',
                'href' => '/dashboard/emergency',
                'icon' => 'alert',
                'color' => 'bg-destructive/10 text-destructive',
                'image' => '/images/support.jpg'
            ],
            [
                'name' => 'Communauté',
                'description' => 'Échangez avec une communauté bienveillante',
                'href' => '/dashboard/forum',
                'icon' => 'users',
                'color' => 'bg-chart-1/10 text-chart-1',
                'image' => '/images/community.jpg'
            ],
        ];

        return $this->render('client/dashboard/index.html.twig', array_merge(
            $this->getUserData(),
            [
                'greeting' => $greeting,
                'userName' => $userName,
                'moodHistory' => $moodHistory,
                'moodOptions' => $moodOptions,
                'modules' => $modules,
            ]
        ));
    }
}