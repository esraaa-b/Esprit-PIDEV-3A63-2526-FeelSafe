<?php

namespace App\Service;

use App\Entity\Utilisateur;
use App\Repository\SessionActiviteRepository;

class WellnessInsightsService
{
    public function __construct(
        private SessionActiviteRepository $sessionRepo
    ) {
    }

    /**
     * Génère des insights personnalisés pour l'utilisateur
     */
    public function getInsights(Utilisateur $user): array
    {
        $insights = [];

        // Récupérer les sessions de l'utilisateur (30 derniers jours)
        // JOIN FETCH activite to avoid N+1 lazy-loading in analyzePreferredActivity()
        $dateDebut = new \DateTime('-30 days');
        $sessions = $this->sessionRepo->createQueryBuilder('s')
            ->addSelect('a')
            ->join('s.activite', 'a')
            ->where('s.utilisateur = :user')
            ->andWhere('s.dateDebut >= :dateDebut')
            ->setParameter('user', $user)
            ->setParameter('dateDebut', $dateDebut)
            ->orderBy('s.dateDebut', 'DESC')
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();

        if (empty($sessions)) {
            return [[
                'icon' => '🌟',
                'message' => 'Commencez votre première session pour voir vos insights personnalisés !',
                'type' => 'neutral'
            ]];
        }

        // Insight 1 : Streak actuel
        $streak = $this->calculateStreak($sessions);
        if ($streak > 0) {
            $insights[] = [
                'icon' => '🔥',
                'message' => $this->getStreakMessage($streak),
                'type' => 'success'
            ];
        }

        // Insight 2 : Amélioration humeur
        $moodImprovement = $this->analyzeMoodImprovement($sessions);
        if ($moodImprovement !== null) {
            $insights[] = [
                'icon' => $moodImprovement > 0 ? '📈' : '📊',
                'message' => $this->getMoodMessage($moodImprovement),
                'type' => $moodImprovement > 0 ? 'success' : 'info'
            ];
        }

        // Insight 3 : Heure préférée
        $preferredTime = $this->analyzePreferredTime($sessions);
        if ($preferredTime) {
            $insights[] = [
                'icon' => $preferredTime['icon'],
                'message' => $preferredTime['message'],
                'type' => 'info'
            ];
        }

        // Insight 4 : Type d'activité préféré
        $preferredActivity = $this->analyzePreferredActivity($sessions);
        if ($preferredActivity) {
            $insights[] = [
                'icon' => '🧘',
                'message' => $preferredActivity,
                'type' => 'info'
            ];
        }

        // Insight 5 : Performance cette semaine
        $weeklyPerformance = $this->analyzeWeeklyPerformance($sessions);
        if ($weeklyPerformance) {
            $insights[] = [
                'icon' => $weeklyPerformance['icon'],
                'message' => $weeklyPerformance['message'],
                'type' => $weeklyPerformance['type']
            ];
        }

        // Limiter à 3 insights max
        return array_slice($insights, 0, 3);
    }

    /**
     * Calculer le streak (jours consécutifs)
     */
    private function calculateStreak(array $sessions): int
    {
        if (empty($sessions)) {
            return 0;
        }

        $streak = 0;
        $currentDate = new \DateTime('today');
        
        // Grouper les sessions par date
        $sessionsByDate = [];
        foreach ($sessions as $session) {
            $date = $session->getDateDebut()->format('Y-m-d');
            if (!isset($sessionsByDate[$date])) {
                $sessionsByDate[$date] = [];
            }
            $sessionsByDate[$date][] = $session;
        }

        // Calculer le streak
        while (true) {
            $dateKey = $currentDate->format('Y-m-d');
            
            if (!isset($sessionsByDate[$dateKey])) {
                break;
            }
            
            // Vérifier s'il y a au moins une session complétée ce jour
            $hasCompleted = false;
            foreach ($sessionsByDate[$dateKey] as $session) {
                if ($session->getStatutSession()->value === 'completee') {
                    $hasCompleted = true;
                    break;
                }
            }
            
            if (!$hasCompleted) {
                break;
            }
            
            $streak++;
            $currentDate->modify('-1 day');
        }

        return $streak;
    }

    /**
     * Message selon le streak
     */
    private function getStreakMessage(int $streak): string
    {
        if ($streak >= 30) {
            return "Incroyable ! Série de {$streak} jours 🌟 Vous êtes une légende !";
        } elseif ($streak >= 14) {
            return "Impressionnant ! Série de {$streak} jours 💪 Continuez ainsi !";
        } elseif ($streak >= 7) {
            return "Bravo ! Série de {$streak} jours 🎉 Une semaine complète !";
        } elseif ($streak >= 3) {
            return "Super ! Série de {$streak} jours 🔥 Vous êtes sur la bonne voie !";
        } else {
            return "Vous avez pratiqué {$streak} jour" . ($streak > 1 ? 's' : '') . " d'affilée ! Continuez !";
        }
    }

    /**
     * Analyser l'amélioration de l'humeur
     */
    private function analyzeMoodImprovement(array $sessions): ?float
    {
        $improvements = [];

        foreach ($sessions as $session) {
            if ($session->getScoreHumeurAvant() && $session->getScoreHumeurApres()) {
                $improvements[] = $session->getScoreHumeurApres() - $session->getScoreHumeurAvant();
            }
        }

        if (empty($improvements)) {
            return null;
        }

        return round(array_sum($improvements) / count($improvements), 1);
    }

    /**
     * Message selon l'amélioration humeur
     */
    private function getMoodMessage(float $improvement): string
    {
        if ($improvement >= 2) {
            return "Excellent ! Votre humeur s'améliore de +{$improvement} points en moyenne après chaque session 🌈";
        } elseif ($improvement >= 1) {
            return "Bien ! Vos sessions améliorent votre humeur de +{$improvement} point en moyenne 😊";
        } elseif ($improvement > 0) {
            return "Vos sessions ont un impact positif sur votre humeur (+{$improvement} point) 🌸";
        } else {
            return "Vos sessions vous aident à maintenir un état d'esprit stable 🧘";
        }
    }

    /**
     * Analyser l'heure préférée
     */
    private function analyzePreferredTime(array $sessions): ?array
    {
        $timeSlots = [
            'morning' => ['count' => 0, 'label' => 'matin', 'range' => '6h-12h', 'icon' => '🌅'],
            'afternoon' => ['count' => 0, 'label' => 'après-midi', 'range' => '12h-18h', 'icon' => '☀️'],
            'evening' => ['count' => 0, 'label' => 'soir', 'range' => '18h-23h', 'icon' => '🌙'],
        ];

        foreach ($sessions as $session) {
            $hour = (int)$session->getDateDebut()->format('H');
            
            if ($hour >= 6 && $hour < 12) {
                $timeSlots['morning']['count']++;
            } elseif ($hour >= 12 && $hour < 18) {
                $timeSlots['afternoon']['count']++;
            } elseif ($hour >= 18 && $hour < 24) {
                $timeSlots['evening']['count']++;
            }
        }

        // Trouver le créneau le plus utilisé
        $maxSlot = null;
        $maxCount = 0;
        
        foreach ($timeSlots as $key => $slot) {
            if ($slot['count'] > $maxCount) {
                $maxCount = $slot['count'];
                $maxSlot = $slot;
            }
        }

        if (!$maxSlot || $maxCount < 2) {
            return null;
        }

        // Suggestion selon l'heure préférée
        $suggestions = [
            'morning' => 'Essayez aussi des sessions le soir pour mieux dormir',
            'afternoon' => 'Une session matinale pourrait vous donner de l\'énergie pour la journée',
            'evening' => 'Essayez aussi le matin pour commencer la journée en douceur',
        ];

        $preferredKey = array_search($maxSlot, $timeSlots);

        return [
            'icon' => $maxSlot['icon'],
            'message' => "Vous pratiquez surtout le {$maxSlot['label']} ({$maxSlot['range']}). {$suggestions[$preferredKey]}"
        ];
    }

    /**
     * Analyser le type d'activité préféré
     */
    private function analyzePreferredActivity(array $sessions): ?string
    {
        $activityTypes = [];

        foreach ($sessions as $session) {
            $type = $session->getActivite()->getTypeActivite();
            $activityTypes[$type] = ($activityTypes[$type] ?? 0) + 1;
        }

        if (empty($activityTypes)) {
            return null;
        }

        arsort($activityTypes);
        $topType = array_key_first($activityTypes);
        $count = $activityTypes[$topType];

        if ($count < 2) {
            return null;
        }

        $suggestions = [
            'Méditation' => 'Essayez aussi le yoga pour varier les plaisirs',
            'Yoga' => 'La méditation pourrait compléter votre pratique',
            'Respiration' => 'Combinez avec de la méditation pour plus d\'impact',
            'Marche' => 'Ajoutez des exercices de respiration pendant vos marches',
            'Écriture thérapeutique' => 'La méditation peut enrichir votre pratique d\'écriture',
        ];

        $suggestion = $suggestions[$topType] ?? 'Continuez à explorer différentes activités';

        return "Vous aimez particulièrement : {$topType}. {$suggestion}";
    }

    /**
     * Analyser la performance hebdomadaire
     */
    private function analyzeWeeklyPerformance(array $sessions): array
    {
        $weekStart = new \DateTime('monday this week');
        $weekSessions = array_filter($sessions, function($session) use ($weekStart) {
            return $session->getDateDebut() >= $weekStart;
        });

        $completedCount = count(array_filter($weekSessions, function($session) {
            return $session->getStatutSession()->value === 'completee';
        }));

        if ($completedCount === 0) {
            return [
                'icon' => '💪',
                'message' => 'Vous n\'avez pas encore complété de session cette semaine. C\'est le moment de commencer !',
                'type' => 'warning'
            ];
        }

        if ($completedCount >= 7) {
            return [
                'icon' => '🏆',
                'message' => "Exceptionnel ! {$completedCount} sessions cette semaine. Vous êtes un champion !",
                'type' => 'success'
            ];
        } elseif ($completedCount >= 5) {
            return [
                'icon' => '⭐',
                'message' => "Excellent ! {$completedCount} sessions cette semaine. Vous êtes très régulier !",
                'type' => 'success'
            ];
        } elseif ($completedCount >= 3) {
            return [
                'icon' => '✨',
                'message' => "Bien joué ! {$completedCount} sessions cette semaine. Continuez comme ça !",
                'type' => 'success'
            ];
        } else {
            return [
                'icon' => '🌱',
                'message' => "{$completedCount} session" . ($completedCount > 1 ? 's' : '') . " cette semaine. Petit à petit, l'oiseau fait son nid !",
                'type' => 'info'
            ];
        }
    }
}