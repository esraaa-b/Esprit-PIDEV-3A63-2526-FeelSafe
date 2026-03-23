<?php

namespace App\Controller\Admin\Api;

use App\Entity\Utilisateur;
use App\Service\HealthDataSimulator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/api/health')]
class HealthDataApiController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private HealthDataSimulator $simulator;

    public function __construct(
        EntityManagerInterface $entityManager,
        HealthDataSimulator $simulator
    ) {
        $this->entityManager = $entityManager;
        $this->simulator = $simulator;
    }

    #[Route('/data', name: 'admin_health_data_all', methods: ['GET'])]
    public function getAllUsersHealthData(Request $request): JsonResponse
    {
        try {
            // Get all users
            $users = $this->entityManager->getRepository(Utilisateur::class)->findAll(); // returns lightweight arrays, not full entities

            $healthData = [];
            foreach ($users as $user) {
                // Generate health data for each user
                $data = $this->simulator->generateHealthData($user->getId());

                // Add user info
                $healthData[] = [
                    'user_id' => $user->getId(),
                    'name' => $user->getPrenom() . ' ' . $user->getNom(),
                    'email' => $user->getEmail(),
                    'health' => $data
                ];
            }

            return new JsonResponse([
                'success' => true,
                'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
                'data' => $healthData
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/data/{userId}', name: 'admin_health_data_user', methods: ['GET'])]
    public function getUserHealthData(int $userId): JsonResponse
    {
        try {
            $user = $this->entityManager->getRepository(Utilisateur::class)->find($userId);

            if (!$user) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'User not found'
                ], Response::HTTP_NOT_FOUND);
            }

            // Generate health data for specific user
            $healthData = $this->simulator->generateHealthData($user->getId());

            return new JsonResponse([
                'success' => true,
                'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
                'data' => [
                    'user_id' => $user->getId(),
                    'name' => $user->getPrenom() . ' ' . $user->getNom(),
                    'email' => $user->getEmail(),
                    'health' => $healthData
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/stats', name: 'admin_health_stats', methods: ['GET'])]
    public function getHealthStatistics(): JsonResponse
    {
        try {
            $users = $this->entityManager->getRepository(Utilisateur::class)->findAll(); // returns lightweight arrays, not full entities
            $stats = [
                'total_users' => count($users),
                'average_heart_rate' => 0,
                'average_stress' => 0,
                'risk_distribution' => [
                    'Low' => 0,
                    'Moderate' => 0,
                    'High' => 0,
                    'Critical' => 0
                ],
                'mood_distribution' => []
            ];

            $totalHeartRate = 0;
            $totalStress = 0;
            $userCount = count($users);

            foreach ($users as $user) {
                $healthData = $this->simulator->generateHealthData($user->getId());

                $totalHeartRate += $healthData['heart_rate'];
                $totalStress += $healthData['stress_level'];

                // Count risk levels
                $stats['risk_distribution'][$healthData['risk_level']] =
                    ($stats['risk_distribution'][$healthData['risk_level']] ?? 0) + 1;

                // Count moods
                $mood = $healthData['mood'];
                $stats['mood_distribution'][$mood] =
                    ($stats['mood_distribution'][$mood] ?? 0) + 1;
            }

            if ($userCount > 0) {
                $stats['average_heart_rate'] = round($totalHeartRate / $userCount, 1);
                $stats['average_stress'] = round($totalStress / $userCount, 1);
            }

            return new JsonResponse([
                'success' => true,
                'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/alerts', name: 'admin_health_alerts', methods: ['GET'])]
    public function getHealthAlerts(): JsonResponse
    {
        try {
            $users = $this->entityManager->getRepository(Utilisateur::class)->findAll(); // returns lightweight arrays, not full entities
            $alerts = [];

            foreach ($users as $user) {
                $healthData = $this->simulator->generateHealthData($user->getId());

                // Check for critical conditions
                if ($healthData['stress_level'] >= 8) {
                    $alerts[] = [
                        'user_id' => $user->getId(),
                        'name' => $user->getPrenom() . ' ' . $user->getNom(),
                        'type' => 'CRITICAL_STRESS',
                        'level' => $healthData['stress_level'],
                        'message' => 'User experiencing critical stress levels',
                        'timestamp' => $healthData['timestamp']
                    ];
                }

                if ($healthData['heart_rate'] > 120) {
                    $alerts[] = [
                        'user_id' => $user->getId(),
                        'name' => $user->getPrenom() . ' ' . $user->getNom(),
                        'type' => 'HIGH_HEART_RATE',
                        'value' => $healthData['heart_rate'],
                        'message' => 'Abnormally high heart rate detected',
                        'timestamp' => $healthData['timestamp']
                    ];
                }

                if ($healthData['risk_level'] === 'Critical') {
                    $alerts[] = [
                        'user_id' => $user->getId(),
                        'name' => $user->getPrenom() . ' ' . $user->getNom(),
                        'type' => 'CRITICAL_RISK',
                        'message' => 'User at critical health risk',
                        'timestamp' => $healthData['timestamp']
                    ];
                }
            }

            return new JsonResponse([
                'success' => true,
                'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
                'alert_count' => count($alerts),
                'alerts' => $alerts
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}