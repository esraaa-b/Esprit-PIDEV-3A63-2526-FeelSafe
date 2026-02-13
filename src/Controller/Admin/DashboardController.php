<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\PublicationRepository;
use App\Repository\JournalEmotionnelRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Component\HttpFoundation\Response;
use App\Enum\RoleUtilisateur;



#[Route('/admin')]
class DashboardController extends AbstractController
{
      #[Route('', name: 'admin_home')]
    public function index(
        PublicationRepository $publicationRepo,
        JournalEmotionnelRepository $journalRepo,
        UtilisateurRepository $userRepo
    ): Response {
        return $this->render('admin/index.html.twig', [
            'nbPublications'   => $publicationRepo->count([]),
            'nbJournals'       => $journalRepo->count([]),
            'nbClients'        => $userRepo->count(['role' => RoleUtilisateur::CLIENT]),
            'nbProfessionnels' => $userRepo->count(['role' => RoleUtilisateur::PROFESSIONNEL]),
        ]);
    }
}
