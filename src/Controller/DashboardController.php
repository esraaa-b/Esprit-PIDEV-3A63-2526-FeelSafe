<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        $user = $this->getUser();
        
        // Rediriger selon le rôle de l'utilisateur
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return $this->redirectToRoute('admin_dashboard');
        }
        
        if (in_array('ROLE_PROFESSIONNEL', $user->getRoles(), true)) {
            return $this->redirectToRoute('app_pro_journal_patients');
        }
        
        // Par défaut, rediriger vers le dashboard client
        return $this->redirectToRoute('app_home');
    }
}