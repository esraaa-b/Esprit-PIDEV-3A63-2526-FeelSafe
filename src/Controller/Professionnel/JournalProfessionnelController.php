<?php

namespace App\Controller\Professionnel;

use App\Entity\Utilisateur;
use App\Entity\JournalEmotionnel;
use App\Entity\TendanceEmotionnelle;
use App\Enum\EmotionEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\Client\BaseDashboardController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Doctrine\Common\Collections\Collection;

#[Route('/professionnel/journal')]
class JournalProfessionnelController extends BaseDashboardController
{
    private function checkIsProfessionnel(Utilisateur $user): bool
    {
        return in_array('ROLE_PROFESSIONNEL', $user->getRoles());
    }

    #[Route('/patients', name: 'app_pro_journal_patients')]
    public function listPatients(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\Utilisateur) {
            throw $this->createAccessDeniedException();
        }
        
        if (!$this->checkIsProfessionnel($user)) {
            throw new AccessDeniedException('Accès non autorisé. Vous devez être professionnel.');
        }
        
        // Récupérer tous les patients (ROLE_CLIENT)
        $patients = $em->getRepository(Utilisateur::class)
            ->createQueryBuilder('u')
            ->leftJoin('u.journaux', 'j')
            ->where('u.role LIKE :role')
            ->setParameter('role', '%ROLE_CLIENT%')
            ->orderBy('u.nom', 'ASC')
            ->addOrderBy('u.prenom', 'ASC')
            ->getQuery()
            ->getResult();

        $patientsData = [];
        foreach ($patients as $patient) {
            $journals = $patient->getJournaux();

            $tendances = $em->getRepository(TendanceEmotionnelle::class)
                ->findBy(
                    ['utilisateur' => $patient],
                    ['dateCalcul' => 'DESC'],
                    5
                );

            $patientsData[] = [
                'patient'          => $patient,
                'journalCount'     => count($journals),
                'lastJournal'      => count($journals) > 0 ? $journals->last() : null,
                'dominantEmotion'  => count($journals) > 0 ? $this->calculateDominantEmotion($journals) : null,
                'recentTendances'  => $tendances,
                'hasAlert'         => count($journals) > 0 ? $this->checkForAlerts($journals) : false,
            ];
        }

        return $this->render('professionnel/journal/patients_list.html.twig', array_merge(
            $this->getUserData(),
            [
                'patientsData' => $patientsData,
                'moodOptions'  => EmotionEnum::cases(),
                'emotionEnum'  => EmotionEnum::class,
            ]
        ));
    }

    #[Route('/patient/{id}/journals', name: 'app_pro_patient_journals')]
    public function patientJournals(Utilisateur $patient, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\Utilisateur) {
            throw $this->createAccessDeniedException();
        }
        
        if (!$this->checkIsProfessionnel($user)) {
            throw new AccessDeniedException('Accès non autorisé. Vous devez être professionnel.');
        }

        if (!in_array('ROLE_CLIENT', $patient->getRoles())) {
            throw new AccessDeniedException('Cet utilisateur n\'est pas un patient');
        }

        $journals = $em->getRepository(JournalEmotionnel::class)
            ->findBy(
                ['utilisateur' => $patient],
                ['dateCreation' => 'DESC']
            );

        $tendances = $em->getRepository(TendanceEmotionnelle::class)
            ->findBy(
                ['utilisateur' => $patient],
                ['dateCalcul' => 'DESC']
            );

        $stats = [
            'total'            => count($journals),
            'dominantEmotion'  => $this->calculateDominantEmotionArray($journals),
            'emotionDistribution' => $this->getEmotionDistributionArray($journals),
            'moyenneHebdo'     => $this->calculateWeeklyAverageArray($journals),
            'evolution'        => $this->calculateEvolutionArray($journals),
            'moyenneHumeur'    => $this->calculateAverageMoodArray($journals),
        ];

        return $this->render('professionnel/journal/index.html.twig', array_merge(
            $this->getUserData(),
            [
                'patient'     => $patient,
                'journals'    => $journals,
                'tendances'   => $tendances,
                'stats'       => $stats,
                'moodOptions' => EmotionEnum::cases(),
                'emotionEnum' => EmotionEnum::class,
            ]
        ));
    }

    #[Route('/{id}/view', name: 'app_pro_journal_view')]
    public function viewJournal(JournalEmotionnel $journal): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\Utilisateur) {
            throw $this->createAccessDeniedException();
        }
        
        if (!$this->checkIsProfessionnel($user)) {
            throw new AccessDeniedException('Accès non autorisé. Vous devez être professionnel.');
        }

        return $this->render('professionnel/journal/_journal_modal.html.twig', [
            'journal'     => $journal,
            'emotionEnum' => EmotionEnum::class,
        ]);
    }

    #[Route('/search', name: 'app_pro_journal_search')]
    public function searchPatients(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\Utilisateur) {
            throw $this->createAccessDeniedException();
        }
        
        if (!$this->checkIsProfessionnel($user)) {
            return $this->json(['error' => 'Accès non autorisé'], 403);
        }

        $term = $request->query->get('q', '');
        
        $patients = $em->getRepository(Utilisateur::class)
            ->createQueryBuilder('u')
            ->where('u.role LIKE :role')
            ->andWhere('u.nom LIKE :term OR u.prenom LIKE :term OR u.email LIKE :term')
            ->setParameter('role', '%ROLE_CLIENT%')
            ->setParameter('term', '%' . $term . '%')
            ->orderBy('u.nom', 'ASC')
            ->addOrderBy('u.prenom', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $results = [];
        foreach ($patients as $patient) {
            $results[] = [
                'id'           => $patient->getId(),
                'nom'          => $patient->getNom(),
                'prenom'       => $patient->getPrenom(),
                'email'        => $patient->getEmail(),
                'fullName'     => $patient->getPrenom() . ' ' . $patient->getNom(),
                'journalCount' => count($patient->getJournaux()),
            ];
        }

        return $this->json($results);
    }

    /**
     * Méthodes pour Collection
     */
    private function calculateDominantEmotion(Collection $journals): ?array
    {
        if (count($journals) === 0) return null;

        $counts = [];
        foreach ($journals as $journal) {
            $emotion = $journal->getEmotion()->value;
            $counts[$emotion] = ($counts[$emotion] ?? 0) + 1;
        }

        arsort($counts);
        $dominantValue = array_key_first($counts);
        $dominantEmotion = EmotionEnum::tryFrom($dominantValue);
        
        return $dominantEmotion ? [
            'emotion'    => $dominantEmotion,
            'count'      => $counts[$dominantValue],
            'percentage' => round(($counts[$dominantValue] / count($journals)) * 100),
        ] : null;
    }

    private function checkForAlerts(Collection $journals): bool
    {
        if (count($journals) === 0) return false;

        $threeDaysAgo = (new \DateTime())->modify('-3 days');
        
        foreach ($journals as $journal) {
            if ($journal->getDateCreation() >= $threeDaysAgo) {
                if (in_array($journal->getEmotion()->value, ['tres_mal', 'pas_bien'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Méthodes pour Array
     */
    private function calculateDominantEmotionArray(array $journals): ?array
    {
        if (count($journals) === 0) return null;

        $counts = [];
        foreach ($journals as $journal) {
            $emotion = $journal->getEmotion()->value;
            $counts[$emotion] = ($counts[$emotion] ?? 0) + 1;
        }

        arsort($counts);
        $dominantValue = array_key_first($counts);
        $dominantEmotion = EmotionEnum::tryFrom($dominantValue);
        
        return $dominantEmotion ? [
            'emotion'    => $dominantEmotion,
            'count'      => $counts[$dominantValue],
            'percentage' => round(($counts[$dominantValue] / count($journals)) * 100),
        ] : null;
    }

    private function getEmotionDistributionArray(array $journals): array
    {
        $distribution = [];
        foreach (EmotionEnum::cases() as $emotion) {
            $distribution[$emotion->value] = 0;
        }

        foreach ($journals as $journal) {
            $distribution[$journal->getEmotion()->value]++;
        }

        return $distribution;
    }

    private function calculateWeeklyAverageArray(array $journals): float
    {
        if (count($journals) === 0) return 0;

        $lastWeek = (new \DateTime())->modify('-7 days');
        $weekJournals = array_filter($journals, fn($j) => $j->getDateCreation() >= $lastWeek);

        return round(count($weekJournals) / 7, 1);
    }

    private function calculateAverageMoodArray(array $journals): ?float
    {
        if (count($journals) === 0) return null;

        $moodValues = [
            'tres_mal' => 1,
            'pas_bien' => 2,
            'neutre'   => 3,
            'bien'     => 4,
            'tres_bien'=> 5,
        ];

        $sum = 0;
        foreach ($journals as $journal) {
            $sum += $moodValues[$journal->getEmotion()->value] ?? 3;
        }

        return round($sum / count($journals), 1);
    }

    private function calculateEvolutionArray(array $journals): array
    {
        if (count($journals) < 5) {
            return ['trend' => 'stable', 'message' => 'Pas assez de données'];
        }

        $recent = array_slice($journals, 0, 5);
        $older  = array_slice($journals, -5, 5);

        $recentAvg = $this->calculateAverageMoodArray($recent);
        $olderAvg  = $this->calculateAverageMoodArray($older);

        if ($recentAvg === null || $olderAvg === null) {
            return ['trend' => 'stable', 'message' => 'Données insuffisantes'];
        }

        $difference = $recentAvg - $olderAvg;

        if ($difference > 0.5) {
            return ['trend' => 'positive', 'message' => 'Amélioration', 'value' => $difference];
        } elseif ($difference < -0.5) {
            return ['trend' => 'negative', 'message' => 'Détérioration', 'value' => $difference];
        } else {
            return ['trend' => 'stable', 'message' => 'Stable', 'value' => $difference];
        }
    }
}
