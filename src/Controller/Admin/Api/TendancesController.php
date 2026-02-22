<?php

namespace App\Controller\Admin\Api;

use App\Entity\Utilisateur;
use App\Entity\JournalEmotionnel;
use App\Entity\TendanceEmotionnelle;
use App\Enum\EmotionEnum;
use App\Service\TendanceGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class TendancesController extends AbstractController
{
    #[Route('/user/{id}/tendances', name: 'api_user_tendances', methods: ['GET'])]
    public function getUserTendances(
        Utilisateur $user, 
        TendanceGenerator $generator,
        EntityManagerInterface $em
    ): JsonResponse {
        try {
            $now = new \DateTime();
            $month = (int)$now->format('m');
            $year = (int)$now->format('Y');
            
            // Générer les tendances
            $generator->generateForMonth($user, $month, $year);
            
            // Récupérer les tendances
            $tendances = $em->getRepository(TendanceEmotionnelle::class)
                ->findBy([
                    'utilisateur' => $user,
                    'mois' => $month,
                    'annee' => $year
                ]);
            
            // Préparer les stats
            $stats = [];
            foreach (EmotionEnum::cases() as $emotion) {
                $found = false;
                foreach ($tendances as $t) {
                    if ($t->getEmotion() === $emotion->value) {
                        $stats[] = [
                            'emotion' => $emotion->value,
                            'label' => $emotion->label(),
                            'icon' => $emotion->icon(),
                            'color' => $this->getColorFromEnum($emotion),
                            'count' => $t->getTotaleOccurrences(),
                            'percentage' => $t->getPourcentage()
                        ];
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $stats[] = [
                        'emotion' => $emotion->value,
                        'label' => $emotion->label(),
                        'icon' => $emotion->icon(),
                        'color' => $this->getColorFromEnum($emotion),
                        'count' => 0,
                        'percentage' => '0'
                    ];
                }
            }
            
            // Données pour le graphique
            $chartLabels = [];
            $chartData = [
                'tres_bien' => [],
                'bien' => [],
                'neutre' => [],
                'pas_bien' => [],
                'tres_mal' => []
            ];
            
            $thirtyDaysAgo = (new \DateTime())->modify('-30 days');
            
            $allJournals = $em->getRepository(JournalEmotionnel::class)
                ->createQueryBuilder('j')
                ->where('j.utilisateur = :user')
                ->andWhere('j.dateCreation >= :thirtyDaysAgo')
                ->setParameter('user', $user)
                ->setParameter('thirtyDaysAgo', $thirtyDaysAgo)
                ->orderBy('j.dateCreation', 'ASC')
                ->getQuery()
                ->getResult();
            
            // Créer un tableau associatif pour un accès rapide
            $journalsByDate = [];
            foreach ($allJournals as $journal) {
                $dateStr = $journal->getDateCreation()->format('d/m/Y');
                if (!isset($journalsByDate[$dateStr])) {
                    $journalsByDate[$dateStr] = [];
                }
                $journalsByDate[$dateStr][] = $journal;
            }
            
            // Remplir les données pour chaque jour
            for ($i = 29; $i >= 0; $i--) {
                $date = new \DateTime("-$i days");
                $dateStr = $date->format('d/m/Y');
                $chartLabels[] = $date->format('d/m');
                
                $counts = [
                    'tres_bien' => 0,
                    'bien' => 0,
                    'neutre' => 0,
                    'pas_bien' => 0,
                    'tres_mal' => 0
                ];
                
                if (isset($journalsByDate[$dateStr])) {
                    foreach ($journalsByDate[$dateStr] as $journal) {
                        $emotion = $journal->getEmotion()->value;
                        if (isset($counts[$emotion])) {
                            $counts[$emotion]++;
                        }
                    }
                }
                
                $chartData['tres_bien'][] = $counts['tres_bien'];
                $chartData['bien'][] = $counts['bien'];
                $chartData['neutre'][] = $counts['neutre'];
                $chartData['pas_bien'][] = $counts['pas_bien'];
                $chartData['tres_mal'][] = $counts['tres_mal'];
            }
            
            return $this->json([
                'stats' => $stats,
                'total' => array_sum(array_column($stats, 'count')),
                'chartLabels' => $chartLabels,
                'chartData' => $chartData
            ]);
            
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    private function getColorFromEnum(EmotionEnum $emotion): string
    {
        return match($emotion) {
            EmotionEnum::TRES_BIEN => '#16a34a',
            EmotionEnum::BIEN => '#86efac',
            EmotionEnum::NEUTRE => '#eab308',
            EmotionEnum::PAS_BIEN => '#fecaca',
            EmotionEnum::TRES_MAL => '#dc2626',
        };
    }

    private function getEmotionClass(EmotionEnum $emotion): string
    {
        return match($emotion) {
            EmotionEnum::TRES_BIEN => 'emotion-tres-bien',
            EmotionEnum::BIEN => 'emotion-bien',
            EmotionEnum::NEUTRE => 'emotion-neutre',
            EmotionEnum::PAS_BIEN => 'emotion-pas-bien',
            EmotionEnum::TRES_MAL => 'emotion-tres-mal',
        };
    }
}