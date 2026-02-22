<?php

namespace App\Enum;

enum ImpactPercu: string
{
    case TRES_POSITIF = 'tres_positif';
    case POSITIF = 'positif';
    case NEUTRE = 'neutre';
    case NEGATIF = 'negatif';
}