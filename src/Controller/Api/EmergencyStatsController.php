<?php

namespace App\Controller\Api;

use App\Entity\Urgence;
use App\Repository\UrgenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/emergency')]
class EmergencyStatsController extends AbstractController
{
    private UrgenceRepository $urgenceRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(UrgenceRepository $urgenceRepository, EntityManagerInterface $entityManager)
    {
        $this->urgenceRepository = $urgenceRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/stats', name: 'api_emergency_stats', methods: ['GET'])]
    public function getStats(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $userId = $user->getId();

        // Get all user's urgences with gravity >= 3 (crises)
        $crises = $this->urgenceRepository->findCrisesLast30Days($userId);

        // Calculate total crises (last 30 days)
        $totalCrises = count($crises);

        // Calculate average severity
        $avgSeverity = 0;
        if ($totalCrises > 0) {
            $sum = array_sum(array_column($crises, 'niveauGravite'));
            $avgSeverity = round($sum / $totalCrises, 1);
        }

        // Calculate streak (days without crisis)
        $streak = $this->urgenceRepository->calculateStreak($userId);

        // Prepare chart data (last 30 days)
        $chartData = [];
        $today = new \DateTime();

        for ($i = 29; $i >= 0; $i--) {
            $date = (clone $today)->modify("-$i days");
            $dayOfMonth = $date->format('j');

            // Find crisis on this day
            $gravity = 0;
            foreach ($crises as $crisis) {
                if ($crisis['dateHeure']->format('Y-m-d') === $date->format('Y-m-d')) {
                    $gravity = $crisis['niveauGravite'];
                    break;
                }
            }

            $chartData[] = [
                'day' => $dayOfMonth,
                'gravity' => $gravity
            ];
        }

        return $this->json([
            'success' => true,
            'total' => $totalCrises,
            'streak' => $streak,
            'avgSeverity' => $avgSeverity,
            'chartData' => $chartData
        ]);
    }

    #[Route('/health-alert', name: 'api_health_alert', methods: ['POST'])]
    public function createHealthAlert(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        // Log the received data for debugging
        error_log('Health alert received: ' . json_encode($data));

        $urgence = new Urgence();
        $urgence->setTypeUrgence($data['type'] ?? 'Alerte SmartWatch');
        $urgence->setDescription($data['description'] ?? 'Alerte santé critique');
        $urgence->setNiveauGravite($data['gravity'] ?? 5);
        $urgence->setStatut(Urgence::STATUT_EN_ATTENTE);
        $urgence->setDateHeure(new \DateTime());
        $urgence->setIdUtilisateur($user->getId());

        $this->entityManager->persist($urgence);
        $this->entityManager->flush();

        error_log('Urgence created with ID: ' . $urgence->getId());

        return $this->json(['success' => true, 'urgence_id' => $urgence->getId()]);
    }
}