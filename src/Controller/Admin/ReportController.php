<?php

namespace App\Controller\Admin;

use App\Entity\Publication;
use App\Entity\Commentaire;
use App\Repository\PublicationRepository;
use App\Repository\CommentaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/reports')]
class ReportController extends AbstractController
{
    #[Route('', name: 'admin_report_index', methods: ['GET'])]
    public function index(Request $request, PublicationRepository $pubRepo, CommentaireRepository $commentRepo): Response
    {
        $status = $request->query->get('status', '');

        $reportedPublications = $pubRepo->findBy(['isReported' => true], ['reportedAt' => 'DESC']);
        $reportedComments = $commentRepo->findBy(['isReported' => true], ['reportedAt' => 'DESC']);

        $items = [];
        foreach ($reportedPublications as $pub) {
            $items[] = [
                'type' => 'publication',
                'entity' => $pub,
                'reason' => $pub->getReportReason(),
                'description' => $pub->getReportDescription(),
                'reportedAt' => $pub->getReportedAt(),
                'status' => $pub->getReportStatus() ?? 'pending',
            ];
        }
        foreach ($reportedComments as $comment) {
            $items[] = [
                'type' => 'comment',
                'entity' => $comment,
                'reason' => $comment->getReportReason(),
                'description' => $comment->getReportDescription(),
                'reportedAt' => $comment->getReportedAt(),
                'status' => $comment->getReportStatus() ?? 'pending',
            ];
        }

        usort($items, function($a, $b) {
            return $b['reportedAt'] <=> $a['reportedAt'];
        });

        $allItems = $items;
        if (in_array($status, ['pending', 'resolved', 'ignored'], true)) {
            $items = array_values(array_filter($items, fn($i) => $i['status'] === $status));
        }

        $pendingCount = count(array_filter($items, fn($i) => $i['status'] === 'pending'));
        $totalCount = count($allItems);

        return $this->render('admin/report/index.html.twig', [
            'reports' => $items,
            'current_status' => $status,
            'pending_count' => $pendingCount,
            'total_count' => $totalCount,
        ]);
    }

    #[Route('/publication/{id}/resolve', name: 'admin_report_publication_resolve', methods: ['POST'])]
    public function resolvePublication(Publication $publication, EntityManagerInterface $em): Response
    {
        // Résoudre = supprimer la publication signalée
        if ($publication->getImage()) {
            $uploadDir = $this->getParameter('uploads_directory');
            $path = $uploadDir . '/' . $publication->getImage();
            if (file_exists($path)) {
                unlink($path);
            }
        }
        $em->remove($publication);
        $this->addFlash('success', 'Publication signalée supprimée.');
        $em->flush();
        return $this->redirectToRoute('admin_report_index');
    }

    #[Route('/publication/{id}/ignore', name: 'admin_report_publication_ignore', methods: ['POST'])]
    public function ignorePublication(Publication $publication, EntityManagerInterface $em): Response
    {
        // Ignorer = marquer comme ignoré mais garder la publication
        $publication->setReportStatus('ignored');
        $em->flush();
        $this->addFlash('success', 'Signalement ignoré. La publication est conservée.');
        return $this->redirectToRoute('admin_report_index');
    }

    #[Route('/publication/{id}/delete', name: 'admin_report_publication_delete', methods: ['POST'])]
    public function deletePublication(Request $request, Publication $publication, EntityManagerInterface $em): Response
    {
        // Supprimer = supprimer uniquement le signalement (reset)
        if (!$this->isCsrfTokenValid('delete_pub' . $publication->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('admin_report_index');
        }

        $publication->setIsReported(false);
        $publication->setReportReason(null);
        $publication->setReportDescription(null);
        $publication->setReportedAt(null);
        $publication->setReportStatus('pending');
        $em->flush();
        $this->addFlash('success', 'Signalement supprimé. La publication est conservée.');
        return $this->redirectToRoute('admin_report_index');
    }

    #[Route('/comment/{id}/resolve', name: 'admin_report_comment_resolve', methods: ['POST'])]
    public function resolveComment(Commentaire $commentaire, EntityManagerInterface $em): Response
    {
        // Résoudre = supprimer le commentaire signalé
        $em->remove($commentaire);
        $this->addFlash('success', 'Commentaire signalé supprimé.');
        $em->flush();
        return $this->redirectToRoute('admin_report_index');
    }

    #[Route('/comment/{id}/ignore', name: 'admin_report_comment_ignore', methods: ['POST'])]
    public function ignoreComment(Commentaire $commentaire, EntityManagerInterface $em): Response
    {
        // Ignorer = marquer comme ignoré mais garder le commentaire
        $commentaire->setReportStatus('ignored');
        $em->flush();
        $this->addFlash('success', 'Signalement ignoré. Le commentaire est conservé.');
        return $this->redirectToRoute('admin_report_index');
    }

    #[Route('/comment/{id}/delete', name: 'admin_report_comment_delete', methods: ['POST'])]
    public function deleteComment(Request $request, Commentaire $commentaire, EntityManagerInterface $em): Response
    {
        // Supprimer = supprimer uniquement le signalement (reset)
        if (!$this->isCsrfTokenValid('delete_comm' . $commentaire->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('admin_report_index');
        }

        $commentaire->setIsReported(false);
        $commentaire->setReportReason(null);
        $commentaire->setReportDescription(null);
        $commentaire->setReportedAt(null);
        $commentaire->setReportStatus('pending');
        $em->flush();
        $this->addFlash('success', 'Signalement supprimé. Le commentaire est conservé.');
        return $this->redirectToRoute('admin_report_index');
    }
}
