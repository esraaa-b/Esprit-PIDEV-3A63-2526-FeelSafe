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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/admin/rdv-accompagnement')]
class RdvAccompagnementController extends AbstractController
{
    #[Route('/', name: 'admin_rdv_accompagnement', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        // Récupérer tous les rendez-vous
        $rendezVous = $em->getRepository(RendezVous::class)->findAll();
        
        // Récupérer tous les accompagnements
        $accompagnements = $em->getRepository(Accompagnement::class)->findAll();
        
        // Récupérer TOUS les utilisateurs
        $utilisateurs = $em->getRepository(Utilisateur::class)->findAll();

        return $this->render('admin/rdv_accompagnement/index.html.twig', [
            'rendezVous' => $rendezVous,
            'accompagnements' => $accompagnements,
            'utilisateurs' => $utilisateurs,
        ]);
    }

    #[Route('/rdv/new', name: 'admin_rdv_new', methods: ['POST'])]
    public function newRdv(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        try {
            $rdv = new RendezVous();
            
            // Récupérer les utilisateurs
            $utilisateur = $em->getRepository(Utilisateur::class)->find($data['utilisateur_id']);
            $professionnel = $em->getRepository(Utilisateur::class)->find($data['professionnel_id']);
            
            // Validation basique
            if (!$utilisateur || !$professionnel) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ], 400);
            }
            
            $rdv->setUtilisateur($utilisateur);
            $rdv->setProfessionnel($professionnel);
            $rdv->setDateRdv(new \DateTime($data['date_rdv']));
            $rdv->setHeureRdv(new \DateTime($data['heure_rdv']));
            
            // Gérer les Enums
            $rdv->setMode(ModeRendezVous::from($data['mode']));
            $rdv->setStatut(StatutRendezVous::from($data['statut']));
            
            $rdv->setLocalisation($data['localisation'] ?? null);
            $rdv->setCommentaire($data['commentaire'] ?? null);
            
            $em->persist($rdv);
            $em->flush();
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Rendez-vous créé avec succès',
                'data' => [
                    'id' => $rdv->getId(),
                    'client' => $rdv->getUtilisateur()->getPrenom() . ' ' . $rdv->getUtilisateur()->getNom(),
                    'professionnel' => $rdv->getProfessionnel()->getPrenom() . ' ' . $rdv->getProfessionnel()->getNom(),
                    'date_rdv' => $rdv->getDateRdv()->format('d/m/Y'),
                    'heure_rdv' => $rdv->getHeureRdv()->format('H:i'),
                ]
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 400);
        }
    }

    #[Route('/rdv/{id}/get', name: 'admin_rdv_get', methods: ['GET'])]
    public function getRdv(int $id, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $rdv = $em->getRepository(RendezVous::class)->find($id);
        
        if (!$rdv) {
            return new JsonResponse(['success' => false, 'message' => 'Rendez-vous non trouvé'], 404);
        }
        
        try {
            // Préparer les données pour le formulaire
            $data = [
                'id' => $rdv->getId(),
                'utilisateur_id' => $rdv->getUtilisateur()->getId(),
                'professionnel_id' => $rdv->getProfessionnel()->getId(),
                'date_rdv' => $rdv->getDateRdv()->format('Y-m-d'),
                'heure_rdv' => $rdv->getHeureRdv()->format('H:i'),
                'mode' => $rdv->getMode()->value,
                'localisation' => $rdv->getLocalisation(),
                'statut' => $rdv->getStatut()->value,
                'commentaire' => $rdv->getCommentaire()
            ];
            
            return new JsonResponse([
                'success' => true,
                'rdv' => $data
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 400);
        }
    }

    #[Route('/rdv/{id}/edit', name: 'admin_rdv_edit', methods: ['POST'])]
    public function editRdv(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $rdv = $em->getRepository(RendezVous::class)->find($id);
        
        if (!$rdv) {
            return new JsonResponse(['success' => false, 'message' => 'Rendez-vous non trouvé'], 404);
        }
        
        try {
            if (isset($data['utilisateur_id'])) {
                $utilisateur = $em->getRepository(Utilisateur::class)->find($data['utilisateur_id']);
                if ($utilisateur) {
                    $rdv->setUtilisateur($utilisateur);
                }
            }
            
            if (isset($data['professionnel_id'])) {
                $professionnel = $em->getRepository(Utilisateur::class)->find($data['professionnel_id']);
                if ($professionnel) {
                    $rdv->setProfessionnel($professionnel);
                }
            }
            
            if (isset($data['date_rdv'])) {
                $rdv->setDateRdv(new \DateTime($data['date_rdv']));
            }
            
            if (isset($data['heure_rdv'])) {
                $rdv->setHeureRdv(new \DateTime($data['heure_rdv']));
            }
            
            if (isset($data['mode'])) {
                $rdv->setMode(ModeRendezVous::from($data['mode']));
            }
            
            if (isset($data['localisation'])) {
                $rdv->setLocalisation($data['localisation']);
            }
            
            if (isset($data['statut'])) {
                $rdv->setStatut(StatutRendezVous::from($data['statut']));
            }
            
            if (isset($data['commentaire'])) {
                $rdv->setCommentaire($data['commentaire']);
            }
            
            $em->flush();
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Rendez-vous modifié avec succès'
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 400);
        }
    }

    #[Route('/rdv/{id}/delete', name: 'admin_rdv_delete', methods: ['DELETE'])]
    public function deleteRdv(int $id, EntityManagerInterface $em): JsonResponse
    {
        $rdv = $em->getRepository(RendezVous::class)->find($id);
        
        if (!$rdv) {
            return new JsonResponse(['success' => false, 'message' => 'Rendez-vous non trouvé'], 404);
        }
        
        try {
            $em->remove($rdv);
            $em->flush();
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Rendez-vous supprimé avec succès'
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 400);
        }
    }

    #[Route('/accompagnement/new', name: 'admin_accompagnement_new', methods: ['POST'])]
    public function newAccompagnement(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        try {
            $accompagnement = new Accompagnement();
            
            // Récupérer le rendez-vous et l'utilisateur
            $rendezVous = $em->getRepository(RendezVous::class)->find($data['rendezvous_id']);
            $utilisateur = $em->getRepository(Utilisateur::class)->find($data['utilisateur_id']);
            
            // Validation basique
            if (!$rendezVous || !$utilisateur) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Rendez-vous ou utilisateur non trouvé'
                ], 400);
            }
            
            $accompagnement->setRendezvous($rendezVous);
            $accompagnement->setUtilisateur($utilisateur);
            $accompagnement->setProchainRdv(ProchainRdv::from($data['prochain_rdv'] ?? 'non'));
            
            if (isset($data['date_prochain_rdv']) && $data['date_prochain_rdv']) {
                $accompagnement->setDateProchainRdv(new \DateTime($data['date_prochain_rdv']));
            }
            
            $accompagnement->setObjectifs($data['objectifs'] ?? null);
            $accompagnement->setNotesSuivi($data['notes_suivi'] ?? null);
            $accompagnement->setNiveauPriorite($data['niveau_priorite'] ?? 3);
            
            $em->persist($accompagnement);
            $em->flush();
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Accompagnement créé avec succès'
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 400);
        }
    }

    #[Route('/accompagnement/{id}/get', name: 'admin_accompagnement_get', methods: ['GET'])]
    public function getAccompagnement(int $id, EntityManagerInterface $em): JsonResponse
    {
        $accompagnement = $em->getRepository(Accompagnement::class)->find($id);
        
        if (!$accompagnement) {
            return new JsonResponse(['success' => false, 'message' => 'Accompagnement non trouvé'], 404);
        }
        
        try {
            // Préparer les données pour le formulaire
            $data = [
                'id' => $accompagnement->getId(),
                'rendezvous_id' => $accompagnement->getRendezvous()->getId(),
                'utilisateur_id' => $accompagnement->getUtilisateur()->getId(),
                'prochain_rdv' => $accompagnement->getProchainRdv()->value,
                'date_prochain_rdv' => $accompagnement->getDateProchainRdv() ? 
                    $accompagnement->getDateProchainRdv()->format('Y-m-d') : null,
                'objectifs' => $accompagnement->getObjectifs(),
                'notes_suivi' => $accompagnement->getNotesSuivi(),
                'niveau_priorite' => $accompagnement->getNiveauPriorite()
            ];
            
            return new JsonResponse([
                'success' => true,
                'accompagnement' => $data
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 400);
        }
    }

    #[Route('/accompagnement/{id}/edit', name: 'admin_accompagnement_edit', methods: ['POST'])]
    public function editAccompagnement(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $accompagnement = $em->getRepository(Accompagnement::class)->find($id);
        
        if (!$accompagnement) {
            return new JsonResponse(['success' => false, 'message' => 'Accompagnement non trouvé'], 404);
        }
        
        try {
            if (isset($data['rendezvous_id'])) {
                $rendezVous = $em->getRepository(RendezVous::class)->find($data['rendezvous_id']);
                if ($rendezVous) {
                    $accompagnement->setRendezvous($rendezVous);
                }
            }
            
            if (isset($data['utilisateur_id'])) {
                $utilisateur = $em->getRepository(Utilisateur::class)->find($data['utilisateur_id']);
                if ($utilisateur) {
                    $accompagnement->setUtilisateur($utilisateur);
                }
            }
            
            if (isset($data['prochain_rdv'])) {
                $accompagnement->setProchainRdv(ProchainRdv::from($data['prochain_rdv']));
            }
            
            if (isset($data['date_prochain_rdv'])) {
                if ($data['date_prochain_rdv']) {
                    $accompagnement->setDateProchainRdv(new \DateTime($data['date_prochain_rdv']));
                } else {
                    $accompagnement->setDateProchainRdv(null);
                }
            }
            
            if (isset($data['objectifs'])) {
                $accompagnement->setObjectifs($data['objectifs']);
            }
            
            if (isset($data['notes_suivi'])) {
                $accompagnement->setNotesSuivi($data['notes_suivi']);
            }
            
            if (isset($data['niveau_priorite'])) {
                $accompagnement->setNiveauPriorite($data['niveau_priorite']);
            }
            
            $em->flush();
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Accompagnement modifié avec succès'
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 400);
        }
    }

    #[Route('/accompagnement/{id}/delete', name: 'admin_accompagnement_delete', methods: ['DELETE'])]
    public function deleteAccompagnement(int $id, EntityManagerInterface $em): JsonResponse
    {
        $accompagnement = $em->getRepository(Accompagnement::class)->find($id);
        
        if (!$accompagnement) {
            return new JsonResponse(['success' => false, 'message' => 'Accompagnement non trouvé'], 404);
        }
        
        try {
            $em->remove($accompagnement);
            $em->flush();
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Accompagnement supprimé avec succès'
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 400);
        }
    }
}