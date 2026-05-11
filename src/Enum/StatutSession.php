<?php

namespace App\Enum;

enum StatutSession: string
{
    case EN_COURS = 'EN_COURS';
    case COMPLETEE = 'COMPLETEE';
    case ABANDONNEE = 'ABANDONNEE';
    case PLANIFIEE = 'PLANIFIEE';

    public static function fromFlexible(string $value): self
    {
        // Essayer d'abord la valeur exacte
        $result = self::tryFrom($value);
        if ($result !== null) return $result;

        // Essayer en majuscules (pour les valeurs Java en minuscules)
        $result = self::tryFrom(strtoupper($value));
        if ($result !== null) return $result;

        throw new \ValueError("\"$value\" is not a valid backing value for enum StatutSession");
    }
}