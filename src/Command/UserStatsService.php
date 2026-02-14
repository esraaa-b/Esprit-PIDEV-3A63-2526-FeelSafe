<?php

namespace App\Service;

use App\Entity\Utilisateur;

class UserStatsService
{
    public function getUserStats(Utilisateur $user): array
    {
        return [
            ['icon' => 'calendar', 'value' => '45', 'label' => 'Sessions'],
            ['icon' => 'book', 'value' => '12', 'label' => 'Programmes'],
            ['icon' => 'clock', 'value' => '24h', 'label' => 'Temps total'],
            ['icon' => 'heart', 'value' => '89%', 'label' => 'Satisfaction'],
        ];
    }

    public function getUserAchievements(Utilisateur $user): array
    {
        return [
            ['icon' => 'book', 'name' => 'Premier pas', 'description' => 'Première session complétée', 'earned' => true],
            ['icon' => 'flame', 'name' => 'En feu', 'description' => '7 jours consécutifs', 'earned' => true],
            ['icon' => 'heart', 'name' => 'Passionné', 'description' => '30 sessions complétées', 'earned' => false],
            ['icon' => 'star', 'name' => 'Expert', 'description' => '100 sessions complétées', 'earned' => false],
            ['icon' => 'book', 'name' => 'Lecteur assidu', 'description' => '10 programmes terminés', 'earned' => true],
            ['icon' => 'flame', 'name' => 'Marathon', 'description' => '30 jours consécutifs', 'earned' => false],
            ['icon' => 'heart', 'name' => 'Mentor', 'description' => 'Aider 5 personnes', 'earned' => false],
            ['icon' => 'star', 'name' => 'Légende', 'description' => '500 sessions complétées', 'earned' => false],
        ];
    }

    public function getProgressData(Utilisateur $user): array
    {
        return [
            'level' => 5,
            'current_xp' => 2450,
            'next_level_xp' => 3000,
            'percentage' => 82,
            'xp_to_next' => 550,
        ];
    }

    public function getDetailedStats(Utilisateur $user): array
    {
        return [
            'total_sessions' => 45,
            'total_programs' => 12,
            'total_time_hours' => 24,
            'average_satisfaction' => 89,
        ];
    }
}