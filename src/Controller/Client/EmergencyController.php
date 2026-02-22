<?php

namespace App\Controller\Client;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\Client\BaseDashboardController;

class EmergencyController extends BaseDashboardController
{
    #[Route('/dashboard/emergency', name: 'app_emergency')]
    public function index(): Response
    {
        return $this->render('client/emergency/index.html.twig', 
            $this->getUserData()
        );
    }
}