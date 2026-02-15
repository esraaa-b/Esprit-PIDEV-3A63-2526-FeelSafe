<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\PublicationRepository;
use App\Repository\JournalEmotionnelRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Component\HttpFoundation\Response;

#[Route('/admin')]
class DashboardController extends AbstractController{
#[Route('', name: 'admin_home')]
public function index(
    PublicationRepository $publicationRepo,
    JournalEmotionnelRepository $journalRepo,
    UtilisateurRepository $userRepo
): Response {

    $nbClients = $userRepo->createQueryBuilder('u')
        ->select('COUNT(u.id)')
        ->where('u.role = :role')
        ->setParameter('role', 'ROLE_CLIENT')
        ->getQuery()
        ->getSingleScalarResult();

    $nbPros = $userRepo->createQueryBuilder('u')
        ->select('COUNT(u.id)')
        ->where('u.role = :role')
        ->setParameter('role', 'ROLE_PROFESSIONNEL')
        ->getQuery()
        ->getSingleScalarResult();

    return $this->render('admin/index.html.twig', [
        'nbPublications'   => $publicationRepo->count([]),
        'nbJournals'       => $journalRepo->count([]),
        'nbClients'        => $nbClients,
        'nbProfessionnels' => $nbPros,
    ]);
}

}
