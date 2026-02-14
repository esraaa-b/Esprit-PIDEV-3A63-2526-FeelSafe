<?php

namespace App\Enum;

enum StatutSession: string
{
    case EN_COURS = 'en_cours';
    case COMPLETEE = 'completee';
    case ABANDONNEE = 'abandonnee';
    case PLANIFIEE = 'planifiee';
}