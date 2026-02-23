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
        // role may be stored as JSON/text; use LIKE to match ROLE_CLIENT
        ->where('u.role LIKE :role')
        ->setParameter('role', '%ROLE_CLIENT%')
        ->getQuery()
        ->getSingleScalarResult();

    $nbPros = $userRepo->createQueryBuilder('u')
        ->select('COUNT(u.id)')
        ->where('u.role LIKE :role')
        ->setParameter('role', '%ROLE_PROFESSIONNEL%')
        ->getQuery()
        ->getSingleScalarResult();

    $nbAdmins = $userRepo->createQueryBuilder('u')
        ->select('COUNT(u.id)')
        ->where('u.role LIKE :role')
        ->setParameter('role', '%ROLE_ADMIN%')
        ->getQuery()
        ->getSingleScalarResult();

    // Recent users
    $recentUsers = $userRepo->createQueryBuilder('u')
        ->orderBy('u.dateCreation', 'DESC')
        ->setMaxResults(5)
        ->getQuery()
        ->getResult();

    // Recent journals
    $recentJournals = $journalRepo->createQueryBuilder('j')
        ->orderBy('j.dateCreation', 'DESC')
        ->setMaxResults(5)
        ->getQuery()
        ->getResult();

    // Build unified recent activities list
    $activities = [];
    foreach ($recentUsers as $u) {
        $dt = $u->getDateCreation();
        $activities[] = [
            'type' => 'user',
            'message' => 'Nouvel utilisateur inscrit : ' . $u->getPrenom() . ' ' . $u->getNom(),
            'date' => $dt?->format('d/m/Y H:i') ?? '',
            'datetime' => $dt,
        ];
    }
    foreach ($recentJournals as $j) {
        $author = $j->getUtilisateur();
        $dt = $j->getDateCreation();
        $activities[] = [
            'type' => 'journal',
            'message' => 'Nouveau journal de ' . ($author ? $author->getPrenom() . ' ' . $author->getNom() : 'Utilisateur'),
            'date' => $dt?->format('d/m/Y H:i') ?? '',
            'datetime' => $dt,
        ];
    }

    // Sort activities by DateTime timestamp desc (most recent first)
    usort($activities, function($a, $b) {
        $ta = $a['datetime'] ? $a['datetime']->getTimestamp() : 0;
        $tb = $b['datetime'] ? $b['datetime']->getTimestamp() : 0;
        return $tb <=> $ta;
    });

    // Keep only the most recent 5 activities
    $activities = array_slice($activities, 0, 4);

    return $this->render('admin/index.html.twig', [
        'nbPublications'   => $publicationRepo->count([]),
        'nbJournals'       => $journalRepo->count([]),
        'nbClients'        => $nbClients,
        'nbProfessionnels' => $nbPros,
        'nbUsers'          => $userRepo->count([]),
        'nbAdmins'         => $nbAdmins,
        'recentActivities' => $activities,
    ]);
}

}
