<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class UserStatsService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Calculer les statistiques d'un utilisateur
     */
    public function getUserStats(User $user): array
    {
        return [
            [
                'icon' => 'calendar',
                'value' => $this->getActiveDays($user),
                'label' => 'Jours actifs'
            ],
            [
                'icon' => 'book',
                'value' => $this->getJournalCount($user),
                'label' => 'Entrées journal'
            ],
            [
                'icon' => 'clock',
                'value' => $this->getPracticeTime($user),
                'label' => 'Temps de pratique'
            ],
            [
                'icon' => 'heart',
                'value' => $this->getCurrentStreak($user),
                'label' => 'Série de jours'
            ],
        ];
    }

    /**
     * Calculer le nombre de jours depuis l'inscription
     */
    private function getActiveDays(User $user): int
    {
        $dateCreation = $user->getDateCreation();
        $now = new \DateTimeImmutable();
        return $dateCreation->diff($now)->days;
    }

    /**
     * Compter le nombre d'entrées de journal
     * À adapter selon votre entité Journal
     */
    private function getJournalCount(User $user): string
    {
        // TODO: Implémenter avec votre entité Journal
        // Exemple:
        // $count = $this->entityManager
        //     ->getRepository(Journal::class)
        //     ->count(['utilisateur' => $user]);
        // return (string) $count;
        
        return '156'; // Valeur par défaut
    }

    /**
     * Calculer le temps de pratique total
     * À adapter selon vos besoins
     */
    private function getPracticeTime(User $user): string
    {
        // TODO: Implémenter selon votre logique métier
        // Exemple:
        // $sessions = $this->entityManager
        //     ->getRepository(Session::class)
        //     ->findBy(['utilisateur' => $user]);
        // $totalMinutes = array_sum(array_map(fn($s) => $s->getDuree(), $sessions));
        // $hours = floor($totalMinutes / 60);
        // return $hours . 'h';
        
        return '89h'; // Valeur par défaut
    }

    /**
     * Calculer la série de jours en cours
     * À adapter selon votre logique métier
     */
    private function getCurrentStreak(User $user): string
    {
        // TODO: Implémenter selon votre logique métier
        // Cette fonction devrait calculer le nombre de jours consécutifs
        // où l'utilisateur a été actif
        
        return '12'; // Valeur par défaut
    }

    /**
     * Obtenir les achievements de l'utilisateur
     */
    public function getUserAchievements(User $user): array
    {
        $activeDays = $this->getActiveDays($user);
        $journalCount = (int) $this->getJournalCount($user);

        return [
            [
                'icon' => 'book',
                'name' => 'Premier journal',
                'description' => 'Première entrée créée',
                'earned' => $journalCount >= 1
            ],
            [
                'icon' => 'flame',
                'name' => '7 jours',
                'description' => 'Série de 7 jours',
                'earned' => $activeDays >= 7
            ],
            [
                'icon' => 'heart',
                'name' => '30 jours',
                'description' => 'Série de 30 jours',
                'earned' => $activeDays >= 30
            ],
            [
                'icon' => 'star',
                'name' => '100 entrées',
                'description' => '100 entrées de journal',
                'earned' => $journalCount >= 100
            ],
            [
                'icon' => 'flame',
                'name' => '90 jours',
                'description' => 'Série de 90 jours',
                'earned' => $activeDays >= 90
            ],
            [
                'icon' => 'star',
                'name' => 'Membre 1 an',
                'description' => 'Membre depuis 1 an',
                'earned' => $activeDays >= 365
            ],
            [
                'icon' => 'heart',
                'name' => '500 entrées',
                'description' => '500 entrées de journal',
                'earned' => $journalCount >= 500
            ],
            [
                'icon' => 'star',
                'name' => 'Contributeur',
                'description' => '10 posts forum',
                'earned' => false // TODO: À calculer selon votre entité Forum
            ],
        ];
    }

    /**
     * Calculer le niveau et l'XP de l'utilisateur
     */
    public function getProgressData(User $user): array
    {
        $activeDays = $this->getActiveDays($user);
        $journalCount = (int) $this->getJournalCount($user);
        
        // XP = (jours actifs * 10) + (journaux * 15)
        $totalXp = ($activeDays * 10) + ($journalCount * 15);
        
        // Chaque niveau nécessite 1000 XP de plus que le précédent
        $level = 1;
        $xpForNextLevel = 1000;
        $remainingXp = $totalXp;
        
        while ($remainingXp >= $xpForNextLevel) {
            $remainingXp -= $xpForNextLevel;
            $level++;
            $xpForNextLevel += 500; // Augmentation progressive
        }
        
        $currentLevelXp = $remainingXp;
        $percentage = ($currentLevelXp / $xpForNextLevel) * 100;
        
        return [
            'level' => $level,
            'current_xp' => $currentLevelXp,
            'next_level_xp' => $xpForNextLevel,
            'total_xp' => $totalXp,
            'percentage' => round($percentage),
            'xp_needed' => $xpForNextLevel - $currentLevelXp
        ];
    }

    /**
     * Obtenir les statistiques détaillées
     */
    public function getDetailedStats(User $user): array
    {
        $activeDays = $this->getActiveDays($user);
        
        return [
            'wellbeing_score' => $this->calculateWellbeingScore($user),
            'current_streak' => (int) $this->getCurrentStreak($user),
            'best_streak' => $this->getBestStreak($user),
            'community_rank' => $this->getCommunityRank($user),
            'total_members' => $this->getTotalMembers()
        ];
    }

    /**
     * Calculer le score de bien-être (0-100)
     */
    private function calculateWellbeingScore(User $user): int
    {
        // TODO: Implémenter selon votre logique métier
        // Exemple: moyenne des humeurs sur les 30 derniers jours
        return 72;
    }

    /**
     * Obtenir la meilleure série de jours
     */
    private function getBestStreak(User $user): int
    {
        // TODO: Implémenter selon votre logique métier
        return 21;
    }

    /**
     * Obtenir le rang de l'utilisateur dans la communauté
     */
    private function getCommunityRank(User $user): int
    {
        // TODO: Implémenter selon votre logique métier
        // Exemple: trier tous les utilisateurs par XP et trouver le rang
        return 156;
    }

    /**
     * Obtenir le nombre total de membres
     */
    private function getTotalMembers(): int
    {
        return $this->entityManager
            ->getRepository(User::class)
            ->count([]);
    }
}