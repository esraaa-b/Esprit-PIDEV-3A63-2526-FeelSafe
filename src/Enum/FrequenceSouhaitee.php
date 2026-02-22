<?php

namespace App\Enum;

enum FrequenceSouhaitee: string
{
    case QUOTIDIENNE = 'quotidienne';
    case HEBDOMADAIRE = 'hebdomadaire';
    case OCCASIONNELLE = 'occasionnelle';
}