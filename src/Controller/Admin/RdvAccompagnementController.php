<?php

namespace App\Controller\Admin;

use App\Entity\RendezVous;
use App\Entity\Accompagnement;
use App\Entity\Utilisateur;
use App\Enum\ModeRendezVous;
use App\Enum\StatutRendezVous;
use App\Enum\ProchainRdv;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/rdv-accompagnement')]
#[IsGranted('ROLE_ADMIN')]
class RdvAccompagnementController extends AbstractController
{
    #[Route('', name: 'admin_rdv_accompagnement', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $rendezVous = $em->getRepository(RendezVous::class)->findBy([], ['dateRdv' => 'DESC', 'heureRdv' => 'DESC']);
        $accompagnements = $em->getRepository(Accompagnement::class)->findBy([], ['id' => 'DESC']);
        $utilisateurs = $em->getRepository(Utilisateur::class)->findBy([], ['prenom' => 'ASC']);
        // Si le repo propose findByRole pour les pros
        if (method_exists($em->getRepository(Utilisateur::class), 'findByRole')) {
            $professionnels = $em->getRepository(Utilisateur::class)->findByRole('ROLE_PROFESSIONNEL');
        } else {
            $professionnels = array_filter($utilisateurs, fn ($u) => $u instanceof Utilisateur && $u->hasRole('ROLE_PROFESSIONNEL'));
        }

        return $this->render('admin/rdv_accompagnement/index.html.twig', [
            'rendezVous' => $rendezVous,
            'accompagnements' => $accompagnements,
            'utilisateurs' => $utilisateurs,
            'professionnels' => $professionnels,
            'modes' => ModeRendezVous::cases(),
            'statuts' => StatutRendezVous::cases(),
        ]);
    }

    #[Route('/rdv/bulk/status', name: 'admin_rdv_bulk_status', methods: ['POST'])]
    public function rdvBulkStatus(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?: [];
        $ids = array_map('intval', $data['ids'] ?? []);
        $statut = $data['statut'] ?? null;
        if (!$ids || !$statut) {
            return $this->json(['success' => false, 'message' => 'IDs ou statut manquant']);
        }
        try {
            $count = 0;
            foreach ($ids as $id) {
                $rdv = $em->getRepository(RendezVous::class)->find($id);
                if ($rdv) {
                    $rdv->setStatut(StatutRendezVous::from($statut));
                    $count++;
                }
            }
            $em->flush();
            return $this->json(['success' => true, 'updated' => $count]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    #[Route('/rdv/bulk/delete', name: 'admin_rdv_bulk_delete', methods: ['POST'])]
    public function rdvBulkDelete(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?: [];
        $ids = array_map('intval', $data['ids'] ?? []);
        if (!$ids) {
            return $this->json(['success' => false, 'message' => 'IDs manquants']);
        }
        try {
            $deleted = 0;
            foreach ($ids as $id) {
                $rdv = $em->getRepository(RendezVous::class)->find($id);
                if ($rdv) {
                    $accs = $em->getRepository(Accompagnement::class)->findBy(['rendezvous' => $rdv]);
                    foreach ($accs as $a) {
                        $em->remove($a);
                    }
                    $em->remove($rdv);
                    $deleted++;
                }
            }
            $em->flush();
            return $this->json(['success' => true, 'deleted' => $deleted]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    #[Route('/accompagnement/bulk/priority', name: 'admin_accompagnement_bulk_priority', methods: ['POST'])]
    public function accompBulkPriority(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?: [];
        $ids = array_map('intval', $data['ids'] ?? []);
        $priorite = isset($data['priorite']) ? (int)$data['priorite'] : null;
        if (!$ids || !$priorite) {
            return $this->json(['success' => false, 'message' => 'IDs ou priorité manquants']);
        }
        try {
            $count = 0;
            foreach ($ids as $id) {
                $a = $em->getRepository(Accompagnement::class)->find($id);
                if ($a) {
                    $a->setNiveauPriorite($priorite);
                    $count++;
                }
            }
            $em->flush();
            return $this->json(['success' => true, 'updated' => $count]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    #[Route('/accompagnement/bulk/delete', name: 'admin_accompagnement_bulk_delete', methods: ['POST'])]
    public function accompBulkDelete(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?: [];
        $ids = array_map('intval', $data['ids'] ?? []);
        if (!$ids) {
            return $this->json(['success' => false, 'message' => 'IDs manquants']);
        }
        try {
            $deleted = 0;
            foreach ($ids as $id) {
                $a = $em->getRepository(Accompagnement::class)->find($id);
                if ($a) {
                    $em->remove($a);
                    $deleted++;
                }
            }
            $em->flush();
            return $this->json(['success' => true, 'deleted' => $deleted]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    #[Route('/rdv/export.csv', name: 'admin_rdv_export', methods: ['GET'])]
    public function rdvExport(EntityManagerInterface $em): Response
    {
        $rdvs = $em->getRepository(RendezVous::class)->findBy([], ['dateRdv' => 'DESC', 'heureRdv' => 'DESC']);
        $rows = [["ID","Patient","Professionnel","Date","Heure","Mode","Statut"]];
        foreach ($rdvs as $r) {
            $rows[] = [
                $r->getId(),
                $r->getUtilisateur()?->getPrenom().' '.$r->getUtilisateur()?->getNom(),
                $r->getProfessionnel()?->getPrenom().' '.$r->getProfessionnel()?->getNom(),
                $r->getDateRdv()?->format('Y-m-d'),
                $r->getHeureRdv()?->format('H:i'),
                $r->getMode()->value,
                $r->getStatut()->value,
            ];
        }
        $csv = '';
        foreach ($rows as $row) { $csv .= implode(';', array_map(fn($v)=>str_replace([';',"\n","\r"],' ',$v), $row))."\r\n"; }
        return new Response($csv, 200, ['Content-Type'=>'text/csv; charset=UTF-8','Content-Disposition'=>'attachment; filename="rdv.csv"']);
    }

    #[Route('/accompagnement/export.csv', name: 'admin_accompagnement_export', methods: ['GET'])]
    public function accompExport(EntityManagerInterface $em): Response
    {
        $accs = $em->getRepository(Accompagnement::class)->findBy([], ['id' => 'DESC']);
        $rows = [["ID","Utilisateur","RDV","Prochain RDV","Date prochain","Priorité","Objectifs","Notes"]];
        foreach ($accs as $a) {
            $rows[] = [
                $a->getId(),
                $a->getUtilisateur()?->getPrenom().' '.$a->getUtilisateur()?->getNom(),
                $a->getRendezvous()?->getId(),
                $a->getProchainRdv()->value,
                $a->getDateProchainRdv()?->format('Y-m-d H:i'),
                $a->getNiveauPriorite(),
                $a->getObjectifs(),
                $a->getNotesSuivi(),
            ];
        }
        $csv = '';
        foreach ($rows as $row) { $csv .= implode(';', array_map(fn($v)=>str_replace([';',"\n","\r"],' ',$v), $row))."\r\n"; }
        return new Response($csv, 200, ['Content-Type'=>'text/csv; charset=UTF-8','Content-Disposition'=>'attachment; filename="accompagnements.csv"']);
    }
    #[Route('/rdv/new', name: 'admin_rdv_new', methods: ['POST'])]
    public function rdvNew(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?: [];
        $userId = (int)($data['utilisateur_id'] ?? 0);
        $proId = (int)($data['professionnel_id'] ?? 0);

        $user = $em->getRepository(Utilisateur::class)->find($userId);
        $pro = $em->getRepository(Utilisateur::class)->find($proId);
        if (!$user || !$pro) {
            return $this->json(['success' => false, 'message' => 'Utilisateur ou professionnel invalide']);
        }
        try {
            $rdv = new RendezVous();
            $rdv->setUtilisateur($user);
            $rdv->setProfessionnel($pro);
            $rdv->setDateRdv(new \DateTime($data['date_rdv'] ?? 'now'));
            $rdv->setHeureRdv(new \DateTime($data['heure_rdv'] ?? '00:00'));
            $rdv->setMode(ModeRendezVous::from($data['mode'] ?? ModeRendezVous::EN_LIGNE->value));
            $rdv->setLocalisation($data['localisation'] ?: null);
            $rdv->setStatut(StatutRendezVous::from($data['statut'] ?? StatutRendezVous::PLANIFIE->value));
            $rdv->setCommentaire($data['commentaire'] ?: null);
            $rdv->setDateCreation(new \DateTime());
            $em->persist($rdv);
            $em->flush();
            return $this->json(['success' => true, 'message' => 'RDV créé', 'id' => $rdv->getId()]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => 'Erreur: '.$e->getMessage()]);
        }
    }

    #[Route('/rdv/{id}/get', name: 'admin_rdv_get', methods: ['GET'])]
    public function rdvGet(int $id, EntityManagerInterface $em): JsonResponse
    {
        $rdv = $em->getRepository(RendezVous::class)->find($id);
        if (!$rdv) {
            return $this->json(['success' => false, 'message' => 'RDV introuvable']);
        }
        return $this->json([
            'success' => true,
            'rdv' => [
                'id' => $rdv->getId(),
                'utilisateur_id' => $rdv->getUtilisateur()?->getId(),
                'professionnel_id' => $rdv->getProfessionnel()?->getId(),
                'date_rdv' => $rdv->getDateRdv()?->format('Y-m-d'),
                'heure_rdv' => $rdv->getHeureRdv()?->format('H:i'),
                'mode' => $rdv->getMode()->value,
                'localisation' => $rdv->getLocalisation(),
                'statut' => $rdv->getStatut()->value,
                'commentaire' => $rdv->getCommentaire(),
            ],
        ]);
    }

    #[Route('/rdv/{id}/edit', name: 'admin_rdv_edit', methods: ['POST'])]
    public function rdvEdit(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $rdv = $em->getRepository(RendezVous::class)->find($id);
        if (!$rdv) {
            return $this->json(['success' => false, 'message' => 'RDV introuvable']);
        }
        $data = json_decode($request->getContent(), true) ?: [];
        try {
            if (!empty($data['utilisateur_id'])) {
                $u = $em->getRepository(Utilisateur::class)->find((int)$data['utilisateur_id']);
                if ($u) $rdv->setUtilisateur($u);
            }
            if (!empty($data['professionnel_id'])) {
                $p = $em->getRepository(Utilisateur::class)->find((int)$data['professionnel_id']);
                if ($p) $rdv->setProfessionnel($p);
            }
            if (!empty($data['date_rdv'])) $rdv->setDateRdv(new \DateTime($data['date_rdv']));
            if (!empty($data['heure_rdv'])) $rdv->setHeureRdv(new \DateTime($data['heure_rdv']));
            if (!empty($data['mode'])) $rdv->setMode(ModeRendezVous::from($data['mode']));
            $rdv->setLocalisation($data['localisation'] ?? null);
            if (!empty($data['statut'])) $rdv->setStatut(StatutRendezVous::from($data['statut']));
            $rdv->setCommentaire($data['commentaire'] ?? null);
            $em->flush();
            return $this->json(['success' => true, 'message' => 'RDV mis à jour']);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => 'Erreur: '.$e->getMessage()]);
        }
    }

    #[Route('/rdv/{id}/delete', name: 'admin_rdv_delete', methods: ['DELETE'])]
    public function rdvDelete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $rdv = $em->getRepository(RendezVous::class)->find($id);
        if (!$rdv) {
            return $this->json(['success' => false, 'message' => 'RDV introuvable']);
        }
        try {
            // supprimer les accompagnements liés d'abord pour éviter contrainte FK
            $accs = $em->getRepository(Accompagnement::class)->findBy(['rendezvous' => $rdv]);
            foreach ($accs as $a) {
                $em->remove($a);
            }
            $em->remove($rdv);
            $em->flush();
            return $this->json(['success' => true, 'message' => 'RDV supprimé']);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => 'Erreur: '.$e->getMessage()]);
        }
    }

    #[Route('/accompagnement/new', name: 'admin_accompagnement_new', methods: ['POST'])]
    public function accompNew(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?: [];
        $rdvId = (int)($data['rendezvous_id'] ?? 0);
        $userId = (int)($data['utilisateur_id'] ?? 0);
        $rdv = $em->getRepository(RendezVous::class)->find($rdvId);
        $user = $em->getRepository(Utilisateur::class)->find($userId);
        if (!$rdv || !$user) {
            return $this->json(['success' => false, 'message' => 'Données invalides']);
        }
        try {
            $acc = new Accompagnement();
            $acc->setRendezvous($rdv);
            $acc->setUtilisateur($user);
            $acc->setProchainRdv(ProchainRdv::from($data['prochain_rdv'] ?? ProchainRdv::NON->value));
            if (!empty($data['date_prochain_rdv'])) {
                $acc->setDateProchainRdv(new \DateTime($data['date_prochain_rdv']));
            } else {
                $acc->setDateProchainRdv(null);
            }
            $acc->setObjectifs($data['objectifs'] ? substr(strip_tags($data['objectifs']), 0, 255) : null);
            $acc->setNotesSuivi($data['notes_suivi'] ? substr(strip_tags($data['notes_suivi']), 0, 255) : null);
            $acc->setNiveauPriorite(isset($data['niveau_priorite']) ? (int)$data['niveau_priorite'] : null);
            $em->persist($acc);
            $em->flush();
            return $this->json(['success' => true, 'message' => 'Accompagnement créé', 'id' => $acc->getId()]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => 'Erreur: '.$e->getMessage()]);
        }
    }

    #[Route('/accompagnement/{id}/get', name: 'admin_accompagnement_get', methods: ['GET'])]
    public function accompGet(int $id, EntityManagerInterface $em): JsonResponse
    {
        $a = $em->getRepository(Accompagnement::class)->find($id);
        if (!$a) {
            return $this->json(['success' => false, 'message' => 'Accompagnement introuvable']);
        }
        return $this->json([
            'success' => true,
            'accompagnement' => [
                'id' => $a->getId(),
                'rendezvous_id' => $a->getRendezvous()?->getId(),
                'utilisateur_id' => $a->getUtilisateur()?->getId(),
                'prochain_rdv' => $a->getProchainRdv()->value,
                'date_prochain_rdv' => $a->getDateProchainRdv()?->format('Y-m-d\TH:i'),
                'objectifs' => $a->getObjectifs(),
                'notes_suivi' => $a->getNotesSuivi(),
                'niveau_priorite' => $a->getNiveauPriorite(),
            ],
        ]);
    }

    #[Route('/accompagnement/{id}/edit', name: 'admin_accompagnement_edit', methods: ['POST'])]
    public function accompEdit(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $a = $em->getRepository(Accompagnement::class)->find($id);
        if (!$a) {
            return $this->json(['success' => false, 'message' => 'Accompagnement introuvable']);
        }
        $data = json_decode($request->getContent(), true) ?: [];
        try {
            if (!empty($data['rendezvous_id'])) {
                $rdv = $em->getRepository(RendezVous::class)->find((int)$data['rendezvous_id']);
                if ($rdv) $a->setRendezvous($rdv);
            }
            if (!empty($data['utilisateur_id'])) {
                $u = $em->getRepository(Utilisateur::class)->find((int)$data['utilisateur_id']);
                if ($u) $a->setUtilisateur($u);
            }
            if (!empty($data['prochain_rdv'])) {
                $a->setProchainRdv(ProchainRdv::from($data['prochain_rdv']));
            }
            if (array_key_exists('date_prochain_rdv', $data)) {
                $a->setDateProchainRdv($data['date_prochain_rdv'] ? new \DateTime($data['date_prochain_rdv']) : null);
            }
            $a->setObjectifs($data['objectifs'] !== null ? substr(strip_tags((string)$data['objectifs']), 0, 255) : $a->getObjectifs());
            $a->setNotesSuivi($data['notes_suivi'] !== null ? substr(strip_tags((string)$data['notes_suivi']), 0, 255) : $a->getNotesSuivi());
            if (array_key_exists('niveau_priorite', $data)) {
                $a->setNiveauPriorite($data['niveau_priorite'] !== null ? (int)$data['niveau_priorite'] : null);
            }
            $em->flush();
            return $this->json(['success' => true, 'message' => 'Accompagnement mis à jour']);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => 'Erreur: '.$e->getMessage()]);
        }
    }

    #[Route('/accompagnement/{id}/delete', name: 'admin_accompagnement_delete', methods: ['DELETE'])]
    public function accompDelete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $a = $em->getRepository(Accompagnement::class)->find($id);
        if (!$a) {
            return $this->json(['success' => false, 'message' => 'Accompagnement introuvable']);
        }
        try {
            $em->remove($a);
            $em->flush();
            return $this->json(['success' => true, 'message' => 'Accompagnement supprimé']);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => 'Erreur: '.$e->getMessage()]);
        }
    }
}
