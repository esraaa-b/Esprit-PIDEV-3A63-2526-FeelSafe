<?php

namespace App\Enum;

enum StatutRendezVous: string
{
    case PLANIFIE = 'planifie';
    case HONORE = 'honore';
    case ANNULE = 'annule';
    case NON_HONORE = 'non_honore';
}
