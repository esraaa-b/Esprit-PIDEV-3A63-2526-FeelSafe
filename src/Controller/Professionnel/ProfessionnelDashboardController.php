<?php
// src/Controller/Professionnel/ProfessionnelDashboardController.php

namespace App\Controller\Professionnel;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProfessionnelDashboardController extends BaseDashboardController
{
    #[Route('/pro/dashboard', name: 'professionnel_dashboard')]
    #[IsGranted('ROLE_PROFESSIONNEL')]
    public function index(): Response
    {
        $hour = (int) date('G');
        if ($hour < 12) {
            $greeting = 'Bonjour';
        } elseif ($hour < 18) {
            $greeting = 'Bon après-midi';
        } else {
            $greeting = 'Bonsoir';
        }

        $user = $this->getUser();
        $userName = $user->getPrenom() ?? 'Professionnel';

        $stats = [
            ['label' => 'Patients actifs',       'value' => 24,  'icon' => 'users',    'color' => 'bg-primary/10 text-primary'],
            ['label' => 'RDV aujourd\'hui',       'value' => 5,   'icon' => 'calendar', 'color' => 'bg-chart-4/10 text-chart-4'],
            ['label' => 'Messages non lus',       'value' => 8,   'icon' => 'mail',     'color' => 'bg-chart-2/10 text-chart-2'],
            ['label' => 'Séances ce mois',        'value' => 67,  'icon' => 'heart',    'color' => 'bg-chart-3/10 text-chart-3'],
        ];

        $todayAppointments = [
            ['time' => '09:00', 'patient' => 'Alice M.',   'type' => 'Suivi',         'status' => 'confirmed'],
            ['time' => '10:30', 'patient' => 'Jean P.',    'type' => 'Première séance','status' => 'confirmed'],
            ['time' => '14:00', 'patient' => 'Sophie D.',  'type' => 'Suivi',         'status' => 'pending'],
            ['time' => '15:30', 'patient' => 'Marc L.',    'type' => 'Suivi',         'status' => 'confirmed'],
            ['time' => '17:00', 'patient' => 'Fatma B.',   'type' => 'Urgence',       'status' => 'urgent'],
        ];

        $recentActivity = [
            ['patient' => 'Alice M.',  'action' => 'A rempli son journal émotionnel', 'time' => 'Il y a 30 min',  'mood' => 'happy'],
            ['patient' => 'Jean P.',   'action' => 'A signalé une situation urgente', 'time' => 'Il y a 1h',     'mood' => 'sad'],
            ['patient' => 'Sophie D.', 'action' => 'A complété un exercice bien-être','time' => 'Il y a 2h',     'mood' => 'neutral'],
        ];

        $modules = [
            [
                'name'        => 'Mes Patients',
                'description' => 'Gérez vos patients et suivez leur progression',
                'href'        => '/pro/dashboard/patients',
                'icon'        => 'users',
                'color'       => 'bg-primary/10 text-primary',
                'image'       => '/images/support.jpg',
            ],
            [
                'name'        => 'Agenda',
                'description' => 'Gérez vos rendez-vous et disponibilités',
                'href'        => '/pro/dashboard/agenda',
                'icon'        => 'calendar',
                'color'       => 'bg-chart-4/10 text-chart-4',
                'image'       => '/images/hero-wellness.jpg',
            ],
            [
                'name'        => 'Messagerie',
                'description' => 'Communiquez avec vos patients en toute sécurité',
                'href'        => '/pro/dashboard/messages',
                'icon'        => 'mail',
                'color'       => 'bg-chart-2/10 text-chart-2',
                'image'       => '/images/community.jpg',
            ],
            [
                'name'        => 'Ressources',
                'description' => 'Partagez des ressources et exercices avec vos patients',
                'href'        => '/pro/dashboard/resources',
                'icon'        => 'book',
                'color'       => 'bg-chart-3/10 text-chart-3',
                'image'       => '/images/journal.jpg',
            ],
            [
                'name'        => 'Urgences',
                'description' => 'Consultez les alertes urgentes de vos patients',
                'href'        => '/pro/dashboard/urgences',
                'icon'        => 'alert',
                'color'       => 'bg-destructive/10 text-destructive',
                'image'       => '/images/support.jpg',
            ],
            [
                'name'        => 'Mon Profil',
                'description' => 'Gérez votre profil professionnel et paramètres',
                'href'        => '/pro/dashboard/profile',
                'icon'        => 'shield',
                'color'       => 'bg-chart-1/10 text-chart-1',
                'image'       => '/images/wellness-activity.jpg',
            ],
        ];

        return $this->render('professionnel/dashboard/index.html.twig', array_merge(
            $this->getUserData(),
            [
                'greeting'           => $greeting,
                'userName'           => $userName,
                'stats'              => $stats,
                'todayAppointments'  => $todayAppointments,
                'recentActivity'     => $recentActivity,
                'modules'            => $modules,
                'userType'           => 'professionnel', // Important pour le template
            ]
        ));
    }
}