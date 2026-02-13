<?php

namespace App\Controller;  // Changed from App\Controller\Dashboard

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EmergencyController extends AbstractController
{
    #[Route('/dashboard/emergency', name: 'app_dashboard_emergency')]
    public function index(): Response
    {
        return $this->render('dashboard/emergency/index.html.twig');
    }
}