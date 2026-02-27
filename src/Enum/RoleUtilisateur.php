<?php

namespace App\Enum;

enum RoleUtilisateur: string
{
    case ADMIN = 'admin';
    case PROFESSIONNEL = 'professionnel';
    case CLIENT = 'client';
}
