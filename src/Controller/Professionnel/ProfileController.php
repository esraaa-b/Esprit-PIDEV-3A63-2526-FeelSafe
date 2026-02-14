<?php

namespace App\Controller\Professionnel;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProfileController extends BaseDashboardController
{
    #[Route('/pro/profile', name: 'app_pro_profile')]
    #[IsGranted('ROLE_PROFESSIONNEL')]
    public function index(): Response
    {
        return $this->render('professionnel/profile/index.html.twig', 
            $this->getUserData()
        );
    }
}