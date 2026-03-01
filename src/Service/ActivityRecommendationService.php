<?php

namespace App\Service;

use App\Entity\ActiviteBienEtre;
use App\Entity\Utilisateur;
use App\Repository\ActiviteBienEtreRepository;
use App\Repository\SessionActiviteRepository;
use Doctrine\ORM\EntityManagerInterface;

class ActivityRecommendationService
{
    public function __construct(
        private WeatherService $weatherService,
        private ActiviteBienEtreRepository $activiteRepo,
        private SessionActiviteRepository $sessionRepo,
        private EntityManagerInterface $em
    ) {
    }
    /**
     * Recommandations intelligentes basées sur météo + historique
     */
    public function getSmartRecommendations(Utilisateur $user, int $limit = 4): array
    {
        $weatherCategory = $this->weatherService->getWeatherCategory();
        $userHistory = $this->getUserPreferences($user);

        $qb = $this->activiteRepo->createQueryBuilder('a')
            ->where('a.estActive = :active')
            ->setParameter('active', true);

        // Filtrer selon la météo
        if ($weatherCategory === 'outdoor') {
            // Activités extérieures en priorité
            $qb->andWhere('a.categorie IN (:outdoorCategories) OR a.typeActivite IN (:outdoorTypes)')
                ->setParameter('outdoorCategories', ['Énergisante', 'Activité physique'])
                ->setParameter('outdoorTypes', ['Marche', 'Yoga', 'Activité physique']);
        } elseif ($weatherCategory === 'indoor') {
            // Activités intérieures en priorité
            $qb->andWhere('a.categorie IN (:indoorCategories) OR a.typeActivite IN (:indoorTypes)')
                ->setParameter('indoorCategories', ['Relaxation', 'Développement personnel'])
                ->setParameter('indoorTypes', ['Méditation', 'Respiration', 'Écriture thérapeutique']);
        }

        // CORRECTION : Exclure les activités déjà faites aujourd'hui
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');

        $todaySessions = $this->sessionRepo->createQueryBuilder('s')
            ->select('IDENTITY(s.activite)')
            ->where('s.utilisateur = :user')
            ->andWhere('s.dateDebut >= :today')
            ->andWhere('s.dateDebut < :tomorrow')
            ->setParameter('user', $user)
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->getQuery()
            ->getResult();

        $todayActivityIds = array_column($todaySessions, 1);
        if (!empty($todayActivityIds)) {
            $qb->andWhere('a.id NOT IN (:todayIds)')
                ->setParameter('todayIds', $todayActivityIds);
        }

        // Prioriser les activités qui ont bien fonctionné pour l'utilisateur
        if (!empty($userHistory['preferred_types'])) {
            $qb->orderBy('CASE WHEN a.typeActivite IN (:preferredTypes) THEN 0 ELSE 1 END', 'ASC')
                ->setParameter('preferredTypes', $userHistory['preferred_types']);
        }

        $qb->addOrderBy('a.dateCreation', 'DESC')
            ->setMaxResults($limit);

        $activities = $qb->getQuery()->getResult();

        // Calculer un score de recommandation pour chaque activité
        return array_map(function ($activity) use ($user, $userHistory) {
            return [
                'activity' => $activity,
                'score' => $this->calculateRecommendationScore($activity, $user, $userHistory),
                'reason' => $this->getRecommendationReason($activity, $userHistory),
            ];
        }, $activities);
    }

    /**
     * Analyse les préférences de l'utilisateur basées sur son historique
     */
    private function getUserPreferences(Utilisateur $user): array
    {
        $sessions = $this->sessionRepo->createQueryBuilder('s')
            ->where('s.utilisateur = :user')
            ->andWhere('s.statutSession = :status')
            ->setParameter('user', $user)
            ->setParameter('status', \App\Enum\StatutSession::COMPLETEE)
            ->getQuery()
            ->getResult();

        if (empty($sessions)) {
            return [
                'preferred_types' => [],
                'avg_duration' => 20,
                'best_time' => 'morning',
                'mood_improvement' => 0,
            ];
        }

        // Analyser les types d'activités préférées
        $typeCount = [];
        $totalDuration = 0;
        $moodImprovements = [];
        $timeSlots = ['morning' => 0, 'afternoon' => 0, 'evening' => 0];

        foreach ($sessions as $session) {
            $type = $session->getActivite()->getTypeActivite();
            $typeCount[$type] = ($typeCount[$type] ?? 0) + 1;

            if ($session->getDureeReelle()) {
                $totalDuration += $session->getDureeReelle();
            }

            // Amélioration humeur
            if ($session->getScoreHumeurAvant() && $session->getScoreHumeurApres()) {
                $moodImprovements[] = $session->getScoreHumeurApres() - $session->getScoreHumeurAvant();
            }

            // Moment de la journée
            $hour = (int) $session->getDateDebut()->format('H');
            if ($hour < 12) {
                $timeSlots['morning']++;
            } elseif ($hour < 18) {
                $timeSlots['afternoon']++;
            } else {
                $timeSlots['evening']++;
            }
        }

        arsort($typeCount);
        $preferredTypes = array_slice(array_keys($typeCount), 0, 3);

        arsort($timeSlots);
        $bestTime = array_key_first($timeSlots);

        return [
            'preferred_types' => $preferredTypes,
            'avg_duration' => count($sessions) > 0 ? round($totalDuration / count($sessions)) : 20,
            'best_time' => $bestTime,
            'mood_improvement' => !empty($moodImprovements) ? array_sum($moodImprovements) / count($moodImprovements) : 0,
        ];
    }

    /**
     * Calcule un score de recommandation (0-100)
     */
    private function calculateRecommendationScore(
        ActiviteBienEtre $activity,
        Utilisateur $user,
        array $userHistory
    ): int {
        $score = 50; // Score de base

        // +20 si type préféré de l'utilisateur
        if (in_array($activity->getTypeActivite(), $userHistory['preferred_types'])) {
            $score += 20;
        }

        // +15 si durée proche de la durée moyenne préférée
        if ($activity->getDureeSuggeree()) {
            $durationDiff = abs($activity->getDureeSuggeree() - $userHistory['avg_duration']);
            if ($durationDiff <= 5) {
                $score += 15;
            } elseif ($durationDiff <= 10) {
                $score += 10;
            }
        }

        // +10 si niveau adapté (facile pour débutants)
        $userSessionCount = $this->sessionRepo->count(['utilisateur' => $user]);
        if ($userSessionCount < 5 && $activity->getNiveauDifficulte()?->value === 'facile') {
            $score += 10;
        }

        // +5 si activité prédéfinie (garantie de qualité)
        if ($activity->isEstPredefinie()) {
            $score += 5;
        }

        return min(100, max(0, $score));
    }

    /**
     * Génère une raison pour la recommandation
     */
    private function getRecommendationReason(ActiviteBienEtre $activity, array $userHistory): string
    {
        if (in_array($activity->getTypeActivite(), $userHistory['preferred_types'])) {
            return "Basé sur vos activités préférées";
        }

        if ($activity->getNiveauDifficulte()?->value === 'facile') {
            return "Idéal pour commencer en douceur";
        }

        if ($activity->isEstPredefinie()) {
            return "Recommandée par nos experts";
        }

        $weatherCategory = $this->weatherService->getWeatherCategory();
        if ($weatherCategory === 'outdoor') {
            return "Parfait pour profiter du beau temps";
        }

        if ($weatherCategory === 'indoor') {
            return "Idéal pour une session au calme";
        }

        return "Suggéré pour votre bien-être";
    }

    /**
     * Recommandation de l'activité du moment basée sur l'heure
     */
    public function getActivityOfTheMoment(Utilisateur $user): ?array
    {
        $hour = (int) date('H');
        $typeActivite = null;

        // Morning: Énergisant
        if ($hour >= 6 && $hour < 12) {
            $typeActivite = ['Yoga', 'Respiration', 'Activité physique'];
        }
        // Afternoon: Équilibrant
        elseif ($hour >= 12 && $hour < 18) {
            $typeActivite = ['Méditation', 'Marche'];
        }
        // Evening: Relaxant
        else {
            $typeActivite = ['Méditation', 'Respiration', 'Écriture thérapeutique'];
        }

        // CORRECTION : Récupérer toutes les activités puis en sélectionner une au hasard en PHP
        $activities = $this->activiteRepo->createQueryBuilder('a')
            ->where('a.estActive = :active')
            ->andWhere('a.typeActivite IN (:types)')
            ->setParameter('active', true)
            ->setParameter('types', $typeActivite)
            ->getQuery()
            ->getResult();

        if (empty($activities)) {
            return null;
        }

        // Sélectionner une activité aléatoire en PHP
        $randomKey = array_rand($activities);
        $activity = $activities[$randomKey];

        return [
            'activity' => $activity,
            'reason' => $this->getMomentReason($hour),
        ];
    }

    private function getMomentReason(int $hour): string
    {
        if ($hour >= 6 && $hour < 12) {
            return "Commencez votre journée avec énergie ! ☀️";
        } elseif ($hour >= 12 && $hour < 18) {
            return "Rechargez vos batteries en milieu de journée ! 🌤️";
        } else {
            return "Détendez-vous après votre journée ! 🌙";
        }
    }
}