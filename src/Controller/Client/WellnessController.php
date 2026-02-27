<?php

namespace App\Controller\Client;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\Client\BaseDashboardController;

class WellnessController extends BaseDashboardController
{
    #[Route('/dashboard/wellness', name: 'app_wellness')]
    public function index(): Response
    {
        $breathingExercises = [
            ['name' => 'Respiration 4-7-8', 'description' => 'Relaxation profonde', 'duration' => '5 min', 'level' => 'Débutant', 'color' => 'bg-primary'],
        ];

        $activities = [
            ['name' => 'Méditation guidée', 'duration' => '10 min', 'category' => 'Méditation'],
        ];

        $weeklyProgress = [
            ['day' => 'Lun', 'completed' => true, 'minutes' => 25],
            ['day' => 'Mar', 'completed' => true, 'minutes' => 30],
            ['day' => 'Mer', 'completed' => true, 'minutes' => 20],
            ['day' => 'Jeu', 'completed' => false, 'minutes' => 0],
            ['day' => 'Ven', 'completed' => true, 'minutes' => 35],
            ['day' => 'Sam', 'completed' => true, 'minutes' => 15],
            ['day' => 'Dim', 'completed' => false, 'minutes' => 0],
        ];

        $totalMinutes = array_sum(array_column($weeklyProgress, 'minutes'));
        $completedDays = count(array_filter($weeklyProgress, fn($day) => $day['completed']));

        return $this->render('client/wellness/index.html.twig', array_merge(
            $this->getUserData(),
            [
                'breathingExercises' => $breathingExercises,
                'activities' => $activities,
                'weeklyProgress' => $weeklyProgress,
                'totalMinutes' => $totalMinutes,
                'completedDays' => $completedDays,
            ]
        ));
    }
}