<?php

namespace App\Controller\Professionnel;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class EmergencyController extends BaseDashboardController
{
    #[Route('/pro/emergency', name: 'app_pro_emergency')]
    #[IsGranted('ROLE_PROFESSIONNEL')]
    public function index(): Response
    {
        return $this->render('professionnel/emergency/index.html.twig', 
            $this->getUserData()
        );
    }
}