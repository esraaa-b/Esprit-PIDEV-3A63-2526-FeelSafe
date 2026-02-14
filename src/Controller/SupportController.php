<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SupportController extends AbstractController
{
    #[Route('/dashboard/support', name: 'app_support')]
    public function index(): Response
    {
        // Professionals data
        $professionals = [
            [
                'id' => 1,
                'name' => 'Dr. Sophie Martin',
                'specialty' => 'Psychologue clinicienne',
                'rating' => 4.9,
                'reviews' => 127,
                'nextAvailable' => 'Demain 14h00',
                'price' => '70',
                'image' => null,
                'online' => true,
            ],
            [
                'id' => 2,
                'name' => 'Dr. Pierre Dubois',
                'specialty' => 'Psychiatre',
                'rating' => 4.8,
                'reviews' => 89,
                'nextAvailable' => 'Vendredi 10h30',
                'price' => '90',
                'image' => null,
                'online' => false,
            ],
            [
                'id' => 3,
                'name' => 'Marie Laurent',
                'specialty' => 'Thérapeute familiale',
                'rating' => 4.7,
                'reviews' => 64,
                'nextAvailable' => 'Lundi 16h00',
                'price' => '65',
                'image' => null,
                'online' => true,
            ],
        ];

        // Upcoming appointments
        $upcomingAppointments = [
            [
                'id' => 1,
                'professional' => 'Dr. Sophie Martin',
                'type' => 'Consultation vidéo',
                'date' => 'Demain',
                'time' => '14h00 - 15h00',
                'status' => 'confirmed',
            ],
            [
                'id' => 2,
                'professional' => 'Marie Laurent',
                'type' => 'Séance en personne',
                'date' => '28 Janvier 2024',
                'time' => '16h00 - 17h00',
                'status' => 'pending',
            ],
        ];

        // Chat messages
        $chatMessages = [
            ['role' => 'assistant', 'content' => 'Bonjour ! Je suis votre assistant bien-être. Comment puis-je vous aider aujourd\'hui ?'],
            ['role' => 'user', 'content' => 'Je me sens stressé ces derniers jours'],
            ['role' => 'assistant', 'content' => 'Je comprends que le stress peut être difficile à gérer. Pouvez-vous me dire ce qui vous cause du stress en ce moment ? Cela m\'aidera à mieux vous accompagner.'],
        ];

        return $this->render('dashboard/support/index.html.twig', [
            'professionals' => $professionals,
            'upcomingAppointments' => $upcomingAppointments,
            'chatMessages' => $chatMessages,
        ]);
    }
}