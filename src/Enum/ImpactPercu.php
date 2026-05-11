<?php

namespace App\Enum;

enum ImpactPercu: string
{
    case TRES_POSITIF = 'TRES_POSITIF';
    case POSITIF = 'POSITIF';
    case NEUTRE = 'NEUTRE';
    case NEGATIF = 'NEGATIF';
}