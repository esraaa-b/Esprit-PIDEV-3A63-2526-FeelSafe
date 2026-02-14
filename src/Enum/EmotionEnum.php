<?php

namespace App\Enum;

enum EmotionEnum: string
{
    case TRES_BIEN = 'tres_bien';
    case BIEN = 'bien';
    case NEUTRE = 'neutre';
    case PAS_BIEN = 'pas_bien';
    case TRES_MAL = 'tres_mal';

      public function label(): string
    {
        return match ($this) {
            self::TRES_BIEN => 'Très bien',
            self::BIEN => 'Bien',
            self::NEUTRE => 'Neutre',
            self::PAS_BIEN => 'Pas bien',
            self::TRES_MAL => 'Très mal',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::TRES_BIEN => '😊',
            self::BIEN => '🙂',
            self::NEUTRE => '😐',
            self::PAS_BIEN => '😕',
            self::TRES_MAL => '😢',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::TRES_BIEN => 'bg-green-600 text-white border-green-600',
            self::BIEN => 'bg-green-300 text-green-900 border-green-300',
            self::NEUTRE => 'bg-yellow-300 text-yellow-900 border-yellow-300',
            self::PAS_BIEN => 'bg-red-200 text-red-900 border-red-200',
            self::TRES_MAL => 'bg-red-600 text-white border-red-600',
        };
    }

    /**
     * Text-only color (for badges, trends, etc.)
     */
    public function textColor(): string
    {
        return match ($this) {
            self::TRES_BIEN => 'text-green-600',
            self::BIEN => 'text-green-400',
            self::NEUTRE => 'text-yellow-500',
            self::PAS_BIEN => 'text-red-400',
            self::TRES_MAL => 'text-red-600',
        };
    }
    public function inactiveColor(): string
{
    return match ($this) {
        self::TRES_BIEN => 'border-green-600 text-green-600 hover:bg-green-50',
        self::BIEN => 'border-green-300 text-green-700 hover:bg-green-50',
        self::NEUTRE => 'border-yellow-300 text-yellow-700 hover:bg-yellow-50',
        self::PAS_BIEN => 'border-red-200 text-red-700 hover:bg-red-50',
        self::TRES_MAL => 'border-red-600 text-red-600 hover:bg-red-50',
    };
}

}
