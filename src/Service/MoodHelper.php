<?php

namespace App\Service;

class MoodHelper
{
    public static function options(): array
    {
        return [
            ['id' => 'very_good', 'label' => 'Très bien', 'icon' => '😊', 'color' => 'bg-green-500'],
            ['id' => 'good', 'label' => 'Bien', 'icon' => '🙂', 'color' => 'bg-green-400'],
            ['id' => 'neutral', 'label' => 'Neutre', 'icon' => '😐', 'color' => 'bg-yellow-400'],
            ['id' => 'bad', 'label' => 'Pas bien', 'icon' => '😔', 'color' => 'bg-orange-400'],
            ['id' => 'very_bad', 'label' => 'Très mal', 'icon' => '😢', 'color' => 'bg-red-500'],
        ];
    }

    public static function map(): array
    {
        return array_column(self::options(), null, 'id');
    }
}
