<?php

namespace App\Controller\Client;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\Client\BaseDashboardController;

class SettingsController extends BaseDashboardController
{
    #[Route('/dashboard/settings', name: 'app_settings')]
    public function index(): Response
    {
        return $this->render('client/settings/index.html.twig',
            $this->getUserData()
        );
    }
}