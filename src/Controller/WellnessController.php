<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WellnessController extends AbstractController
{
    #[Route('/dashboard/wellness', name: 'app_wellness')]
    public function index(): Response
    {
        // Breathing exercises
        $breathingExercises = [
            [
                'id' => 1,
                'name' => 'Respiration 4-7-8',
                'description' => 'Technique de relaxation profonde',
                'duration' => '5 min',
                'level' => 'Débutant',
                'color' => 'bg-primary',
            ],
            [
                'id' => 2,
                'name' => 'Cohérence cardiaque',
                'description' => 'Équilibrez votre système nerveux',
                'duration' => '5 min',
                'level' => 'Intermédiaire',
                'color' => 'bg-chart-4',
            ],
            [
                'id' => 3,
                'name' => 'Respiration abdominale',
                'description' => 'Réduisez le stress instantanément',
                'duration' => '3 min',
                'level' => 'Débutant',
                'color' => 'bg-chart-2',
            ],
        ];

        // Activities
        $activities = [
            [
                'id' => 1,
                'name' => 'Méditation guidée',
                'duration' => '10 min',
                'category' => 'Méditation',
            ],
            [
                'id' => 2,
                'name' => 'Yoga du soir',
                'duration' => '20 min',
                'category' => 'Yoga',
            ],
            [
                'id' => 3,
                'name' => 'Musique relaxante',
                'duration' => '30 min',
                'category' => 'Audio',
            ],
            [
                'id' => 4,
                'name' => 'Routine du matin',
                'duration' => '15 min',
                'category' => 'Routine',
            ],
        ];

        // Weekly progress
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

        return $this->render('dashboard/wellness/index.html.twig', [
            'breathingExercises' => $breathingExercises,
            'activities' => $activities,
            'weeklyProgress' => $weeklyProgress,
            'totalMinutes' => $totalMinutes,
            'completedDays' => $completedDays,
        ]);
    }
}