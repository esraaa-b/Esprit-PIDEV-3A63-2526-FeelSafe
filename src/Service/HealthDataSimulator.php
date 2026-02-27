<?php

namespace App\Service;

class HealthDataSimulator
{
    // Heart rate ranges based on activity/stress
    private const HEART_RATE_RANGES = [
        'resting' => ['min' => 60, 'max' => 80],
        'normal' => ['min' => 70, 'max' => 90],
        'elevated' => ['min' => 85, 'max' => 105],
        'high' => ['min' => 100, 'max' => 130],
        'very_high' => ['min' => 120, 'max' => 160]
    ];

    // Stress levels (1-10) with descriptions
    private const STRESS_LEVELS = [
        ['level' => 1, 'label' => 'Very Low', 'description' => 'Completely relaxed'],
        ['level' => 2, 'label' => 'Low', 'description' => 'Minimal stress'],
        ['level' => 3, 'label' => 'Low-Moderate', 'description' => 'Slightly tense'],
        ['level' => 4, 'label' => 'Moderate', 'description' => 'Noticeable stress'],
        ['level' => 5, 'label' => 'Moderate-High', 'description' => 'Feeling pressured'],
        ['level' => 6, 'label' => 'High', 'description' => 'Very tense'],
        ['level' => 7, 'label' => 'Very High', 'description' => 'Extremely stressed'],
        ['level' => 8, 'label' => 'Severe', 'description' => 'Overwhelmed'],
        ['level' => 9, 'label' => 'Critical', 'description' => 'Panic level'],
        ['level' => 10, 'label' => 'Emergency', 'description' => 'Medical emergency']
    ];

    // Moods with associated stress ranges
    private const MOODS = [
        ['mood' => 'Calm', 'stress_range' => [1, 3], 'heart_rate_range' => 'resting'],
        ['mood' => 'Relaxed', 'stress_range' => [1, 2], 'heart_rate_range' => 'resting'],
        ['mood' => 'Happy', 'stress_range' => [1, 3], 'heart_rate_range' => 'normal'],
        ['mood' => 'Content', 'stress_range' => [2, 4], 'heart_rate_range' => 'normal'],
        ['mood' => 'Anxious', 'stress_range' => [5, 7], 'heart_rate_range' => 'elevated'],
        ['mood' => 'Stressed', 'stress_range' => [6, 8], 'heart_rate_range' => 'high'],
        ['mood' => 'Very Stressed', 'stress_range' => [7, 9], 'heart_rate_range' => 'very_high'],
        ['mood' => 'Panicked', 'stress_range' => [8, 10], 'heart_rate_range' => 'very_high'],
        ['mood' => 'Tired', 'stress_range' => [3, 5], 'heart_rate_range' => 'normal'],
        ['mood' => 'Energetic', 'stress_range' => [2, 4], 'heart_rate_range' => 'elevated']
    ];

    /**
     * Generate a complete health data set for a user
     */
    public function generateHealthData(int $userId = null): array
    {
        // Select random mood
        $moodData = $this->getRandomMood();

        // Generate stress level within mood's range
        $stressLevel = $this->randomInt(
            $moodData['stress_range'][0],
            $moodData['stress_range'][1]
        );

        // Get heart rate range based on mood
        $heartRateRange = self::HEART_RATE_RANGES[$moodData['heart_rate_range']];
        $heartRate = $this->randomInt($heartRateRange['min'], $heartRateRange['max']);

        // Add some natural variation
        if ($this->randomBool(30)) { // 30% chance of variation
            $heartRate += $this->randomInt(-5, 5);
        }

        return [
            'user_id' => $userId,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
            'heart_rate' => $heartRate,
            'stress_level' => $stressLevel,
            'stress_label' => $this->getStressLabel($stressLevel),
            'stress_description' => $this->getStressDescription($stressLevel),
            'mood' => $moodData['mood'],
            'blood_pressure' => $this->generateBloodPressure($stressLevel),
            'oxygen_level' => $this->generateOxygenLevel($stressLevel),
            'sleep_quality' => $this->generateSleepQuality(),
            'activity_level' => $this->generateActivityLevel(),
            'risk_level' => $this->calculateRiskLevel($stressLevel, $heartRate)
        ];
    }

    /**
     * Generate health data for multiple users
     */
    public function generateBulkHealthData(array $userIds = []): array
    {
        $data = [];
        foreach ($userIds as $userId) {
            $data[] = $this->generateHealthData($userId);
        }
        return $data;
    }

    /**
     * Generate a realistic blood pressure reading
     */
    private function generateBloodPressure(int $stressLevel): array
    {
        // Systolic (top number) - increases with stress
        $baseSystolic = 110;
        $systolic = $baseSystolic + ($stressLevel * 3) + $this->randomInt(-5, 5);

        // Diastolic (bottom number)
        $baseDiastolic = 70;
        $diastolic = $baseDiastolic + ($stressLevel * 2) + $this->randomInt(-3, 3);

        return [
            'systolic' => $systolic,
            'diastolic' => $diastolic,
            'reading' => $systolic . '/' . $diastolic,
            'category' => $this->getBloodPressureCategory($systolic, $diastolic)
        ];
    }

    /**
     * Generate oxygen level (SpO2)
     */
    private function generateOxygenLevel(int $stressLevel): array
    {
        // Normal is 95-100
        $baseOxygen = 98;
        $oxygen = $baseOxygen - ($stressLevel * 0.2) + $this->randomFloat(-1, 1);
        $oxygen = max(90, min(100, round($oxygen, 1))); // Keep between 90-100

        return [
            'value' => $oxygen,
            'status' => $oxygen >= 95 ? 'Normal' : ($oxygen >= 90 ? 'Low' : 'Critical'),
            'unit' => '%'
        ];
    }

    /**
     * Generate sleep quality data (if time is appropriate)
     */
    private function generateSleepQuality(): array
    {
        $hour = (int) date('H');

        // During night/early morning, generate sleep data
        if ($hour >= 23 || $hour <= 6) {
            $quality = $this->randomInt(60, 98);
            return [
                'value' => $quality,
                'status' => $quality >= 80 ? 'Good' : ($quality >= 60 ? 'Fair' : 'Poor'),
                'unit' => '%',
                'hours' => round($this->randomFloat(5, 9), 1)
            ];
        }

        // During day, user is awake
        return [
            'value' => null,
            'status' => 'Awake',
            'unit' => null,
            'hours' => null
        ];
    }

    /**
     * Generate activity level
     */
    private function generateActivityLevel(): array
    {
        $steps = $this->randomInt(0, 15000);
        $calories = round($steps * 0.04); // Rough estimate

        return [
            'steps' => $steps,
            'calories_burned' => $calories,
            'active_minutes' => $this->randomInt(0, 180),
            'status' => $steps < 5000 ? 'Low' : ($steps < 10000 ? 'Moderate' : 'High')
        ];
    }

    /**
     * Calculate overall risk level
     */
    private function calculateRiskLevel(int $stressLevel, int $heartRate): string
    {
        $score = 0;

        // Stress contribution
        $score += $stressLevel * 2;

        // Heart rate contribution
        if ($heartRate > 100)
            $score += 3;
        else if ($heartRate > 85)
            $score += 1;

        if ($score >= 15)
            return 'Critical';
        if ($score >= 10)
            return 'High';
        if ($score >= 5)
            return 'Moderate';
        return 'Low';
    }

    /**
     * Helper: Get random mood
     */
    private function getRandomMood(): array
    {
        return self::MOODS[array_rand(self::MOODS)];
    }

    /**
     * Helper: Get stress label by level
     */
    private function getStressLabel(int $level): string
    {
        foreach (self::STRESS_LEVELS as $stress) {
            if ($stress['level'] === $level) {
                return $stress['label'];
            }
        }
        return 'Unknown';
    }

    /**
     * Helper: Get stress description by level
     */
    private function getStressDescription(int $level): string
    {
        foreach (self::STRESS_LEVELS as $stress) {
            if ($stress['level'] === $level) {
                return $stress['description'];
            }
        }
        return '';
    }

    /**
     * Helper: Get blood pressure category
     */
    private function getBloodPressureCategory(int $systolic, int $diastolic): string
    {
        if ($systolic >= 180 || $diastolic >= 120)
            return 'Hypertensive Crisis';
        if ($systolic >= 140 || $diastolic >= 90)
            return 'High (Hypertension Stage 2)';
        if ($systolic >= 130 || $diastolic >= 80)
            return 'Elevated (Hypertension Stage 1)';
        if ($systolic >= 120)
            return 'Elevated';
        return 'Normal';
    }

    /**
     * Helper: Random integer
     */
    private function randomInt(int $min, int $max): int
    {
        return rand($min, $max);
    }

    /**
     * Helper: Random float
     */
    private function randomFloat(float $min, float $max): float
    {
        return $min + mt_rand() / mt_getrandmax() * ($max - $min);
    }

    /**
     * Helper: Random boolean with probability
     */
    private function randomBool(int $percentage): bool
    {
        return rand(1, 100) <= $percentage;
    }
}