<?php

namespace App\Enum;

enum HumeurEnum: string
{
    case TRES_BIEN = 'TRES_BIEN';
    case BIEN = 'BIEN';
    case NEUTRE = 'NEUTRE';
    case PAS_BIEN = 'PAS_BIEN';
    case TRES_MAL = 'TRES_MAL';
}