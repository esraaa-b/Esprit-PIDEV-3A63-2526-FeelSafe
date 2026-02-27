<?php

namespace App\Enum;

enum HumeurEnum: string
{
    case TRES_BIEN = 'tres_bien';
    case BIEN = 'bien';
    case NEUTRE = 'neutre';
    case PAS_BIEN = 'pas_bien';
    case TRES_MAL = 'tres_mal';
}