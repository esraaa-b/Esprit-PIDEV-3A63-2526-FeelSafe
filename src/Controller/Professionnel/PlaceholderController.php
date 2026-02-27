<?php
// src/Controller/Professionnel/PlaceholderController.php

namespace App\Controller\Professionnel;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PlaceholderController extends BaseDashboardController
{
    #[Route('/pro/dashboard/patients', name: 'app_pro_patients')]
    #[IsGranted('ROLE_PROFESSIONNEL')]
    public function patients(): Response
    {
        return new Response('<div style="padding:16px;font-family:system-ui">Mes Patients — page en cours de construction</div>');
    }

    #[Route('/pro/dashboard/agenda', name: 'app_pro_agenda')]
    #[IsGranted('ROLE_PROFESSIONNEL')]
    public function agenda(): Response
    {
        return new Response('<div style="padding:16px;font-family:system-ui">Agenda — page en cours de construction</div>');
    }

    #[Route('/pro/dashboard/messages', name: 'app_pro_messages')]
    #[IsGranted('ROLE_PROFESSIONNEL')]
    public function messages(): Response
    {
        return new Response('<div style="padding:16px;font-family:system-ui">Messagerie — page en cours de construction</div>');
    }

    #[Route('/pro/dashboard/resources', name: 'app_pro_resources')]
    #[IsGranted('ROLE_PROFESSIONNEL')]
    public function resources(): Response
    {
        return new Response('<div style="padding:16px;font-family:system-ui">Ressources — page en cours de construction</div>');
    }
}

