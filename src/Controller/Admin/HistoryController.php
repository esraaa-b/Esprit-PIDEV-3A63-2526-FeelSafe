<?php

namespace App\Controller\Admin;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/history')]
#[IsGranted('ROLE_ADMIN')]
class HistoryController extends AbstractController
{
    #[Route('', name: 'admin_history', methods: ['GET'])]
    public function index(Connection $db): Response
    {
        $sql = "
            SELECT d.id, d.id_rendez_vous, d.id_professionnel, d.action, d.ancien_statut, d.nouveau_statut, d.commentaire, d.date_action,
                   pro.prenom AS pro_prenom, pro.nom AS pro_nom,
                   r.date_rdv, r.heure_rdv, r.utilisateur_id AS client_id,
                   cli.prenom AS client_prenom, cli.nom AS client_nom
            FROM rendez_vous_decision d
            LEFT JOIN utilisateur pro ON pro.id = d.id_professionnel
            LEFT JOIN rendez_vous r ON r.id = d.id_rendez_vous
            LEFT JOIN utilisateur cli ON cli.id = r.utilisateur_id
            ORDER BY d.date_action DESC
            LIMIT 500
        ";
        $items = $db->fetchAllAssociative($sql);
        return $this->render('admin/history/index.html.twig', [
            'items' => $items,
        ]);
    }

    #[Route('/list', name: 'admin_history_list', methods: ['GET'])]
    public function list(Connection $db): JsonResponse
    {
        try {
            $sql = "
                SELECT d.id, d.id_rendez_vous, d.id_professionnel, d.action, d.ancien_statut, d.nouveau_statut, d.commentaire, d.date_action,
                       pro.prenom AS pro_prenom, pro.nom AS pro_nom,
                       r.date_rdv, r.heure_rdv, r.utilisateur_id AS client_id,
                       cli.prenom AS client_prenom, cli.nom AS client_nom
                FROM rendez_vous_decision d
                LEFT JOIN utilisateur pro ON pro.id = d.id_professionnel
                LEFT JOIN rendez_vous r ON r.id = d.id_rendez_vous
                LEFT JOIN utilisateur cli ON cli.id = r.utilisateur_id
                ORDER BY d.date_action DESC
                LIMIT 500
            ";
            $items = $db->fetchAllAssociative($sql);
            return $this->json(['success' => true, 'items' => $items]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => 'DB_ERROR', 'message' => $e->getMessage()]);
        }
    }
    #[Route('/clear', name: 'admin_history_clear', methods: ['POST'])]
    public function clear(Connection $db): JsonResponse
    {
        $db->executeStatement('DELETE FROM rendez_vous_decision');
        return $this->json(['success' => true]);
    }

    #[Route('/delete/{id}', name: 'admin_history_delete', methods: ['DELETE'])]
    public function delete(int $id, Connection $db): JsonResponse
    {
        $affected = $db->executeStatement('DELETE FROM rendez_vous_decision WHERE id = :id', ['id' => $id]);
        return $this->json(['success' => $affected > 0]);
    }
}
