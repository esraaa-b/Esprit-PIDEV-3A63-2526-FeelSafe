<?php

namespace App\Controller\Client;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\Client\BaseDashboardController;

class SupportController extends BaseDashboardController
{
    #[Route('/dashboard/support', name: 'app_support')]
    public function index(): Response
    {
        $professionals = [
            [
                'name' => 'Dr. Sophie Martin',
                'specialty' => 'Psychologue',
                'rating' => 4.9,
                'reviews' => 127,
                'nextAvailable' => 'Demain 14h00',
                'price' => '70',
                'online' => true,
            ],
        ];

        $upcomingAppointments = [];

        $chatMessages = [
            ['role' => 'assistant', 'content' => 'Bonjour ! Comment puis-je vous aider ?'],
        ];

        return $this->render('client/support/index.html.twig', array_merge(
            $this->getUserData(),
            [
                'professionals' => $professionals,
                'upcomingAppointments' => $upcomingAppointments,
                'chatMessages' => $chatMessages,
            ]
        ));
    }
}