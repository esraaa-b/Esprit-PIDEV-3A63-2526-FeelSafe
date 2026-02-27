<?php

namespace App\Controller\Admin;

use App\Entity\ActiviteBienEtre;
use App\Entity\SessionActivite;
use App\Repository\ActiviteBienEtreRepository;
use App\Repository\SessionActiviteRepository;
use App\Enum\NiveauDifficulte;
use App\Enum\StatutSession;
use App\Enum\HumeurEnum;
use App\Enum\ImpactPercu;
use App\Enum\FrequenceSouhaitee;
use App\Enum\RecommandePar;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/wellness')]
class WellnessController extends AbstractController
{
    // ================== DASHBOARD ==================
    
    #[Route('', name: 'admin_wellness')]
    public function index(
        ActiviteBienEtreRepository $activiteRepo,
        SessionActiviteRepository $sessionRepo
    ): Response {
        $statsActivites = $activiteRepo->getStatistics();
        $statsSessions = $sessionRepo->getStatistics();
        $sessionsRecentes = $sessionRepo->findRecent(7);

        return $this->render('admin/wellness/index.html.twig', [
            'statsActivites' => $statsActivites,
            'statsSessions' => $statsSessions,
            'sessionsRecentes' => $sessionsRecentes,
        ]);
    }

    // ================== ACTIVITÉS - CRUD ==================
    
    #[Route('/activites', name: 'admin_wellness_activites')]
    public function listeActivites(ActiviteBienEtreRepository $repo): Response
    {
        $activites = $repo->findAll();
        
        return $this->render('admin/wellness/activites/index.html.twig', [
            'activites' => $activites,
        ]);
    }

    #[Route('/activites/nouvelle', name: 'admin_wellness_activites_new')]
    public function nouvelleActivite(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $activite = new ActiviteBienEtre();
            $activite->setNomActivite($request->request->get('nom_activite'));
            $activite->setTypeActivite($request->request->get('type_activite'));
            $activite->setDescription($request->request->get('description'));
            $activite->setDureeSuggeree($request->request->get('duree_suggeree'));
            $activite->setCategorie($request->request->get('categorie'));
            
            $niveauDifficulte = $request->request->get('niveau_difficulte');
            if ($niveauDifficulte) {
                $activite->setNiveauDifficulte(NiveauDifficulte::from($niveauDifficulte));
            }
            
            $activite->setObjectifEmotionnel($request->request->get('objectif_emotionnel'));
            $activite->setEstPredefinie($request->request->get('est_predefinie') === '1');
            $activite->setEstActive($request->request->get('est_active') === '1');
            $activite->setImageUrl($request->request->get('image_url'));
            $activite->setInstructionsDetaillees($request->request->get('instructions_detaillees'));

            $em->persist($activite);
            $em->flush();

            $this->addFlash('success', 'Activité créée avec succès !');
            return $this->redirectToRoute('admin_wellness_activites');
        }

        return $this->render('admin/wellness/activites/new.html.twig', [
            'niveauxDifficulte' => NiveauDifficulte::cases(),
        ]);
    }

    #[Route('/activites/{id}/modifier', name: 'admin_wellness_activites_edit')]
    public function modifierActivite(
        ActiviteBienEtre $activite,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if ($request->isMethod('POST')) {
            $activite->setNomActivite($request->request->get('nom_activite'));
            $activite->setTypeActivite($request->request->get('type_activite'));
            $activite->setDescription($request->request->get('description'));
            $activite->setDureeSuggeree($request->request->get('duree_suggeree'));
            $activite->setCategorie($request->request->get('categorie'));
            
            $niveauDifficulte = $request->request->get('niveau_difficulte');
            if ($niveauDifficulte) {
                $activite->setNiveauDifficulte(NiveauDifficulte::from($niveauDifficulte));
            }
            
            $activite->setObjectifEmotionnel($request->request->get('objectif_emotionnel'));
            $activite->setEstPredefinie($request->request->get('est_predefinie') === '1');
            $activite->setEstActive($request->request->get('est_active') === '1');
            $activite->setImageUrl($request->request->get('image_url'));
            $activite->setInstructionsDetaillees($request->request->get('instructions_detaillees'));

            $em->flush();

            $this->addFlash('success', 'Activité modifiée avec succès !');
            return $this->redirectToRoute('admin_wellness_activites');
        }

        return $this->render('admin/wellness/activites/edit.html.twig', [
            'activite' => $activite,
            'niveauxDifficulte' => NiveauDifficulte::cases(),
        ]);
    }

    #[Route('/activites/{id}', name: 'admin_wellness_activites_show')]
    public function afficherActivite(ActiviteBienEtre $activite): Response
    {
        return $this->render('admin/wellness/activites/show.html.twig', [
            'activite' => $activite,
        ]);
    }

    #[Route('/activites/{id}/supprimer', name: 'admin_wellness_activites_delete', methods: ['POST'])]
    public function supprimerActivite(
        ActiviteBienEtre $activite,
        EntityManagerInterface $em
    ): Response {
        $em->remove($activite);
        $em->flush();

        $this->addFlash('success', 'Activité supprimée avec succès !');
        return $this->redirectToRoute('admin_wellness_activites');
    }

    // ================== SESSIONS - CONSULTATION SEULEMENT ==================
    
    #[Route('/sessions', name: 'admin_wellness_sessions')]
    public function listeSessions(SessionActiviteRepository $repo): Response
    {
        $sessions = $repo->findBy([], ['dateDebut' => 'DESC']);
        
        return $this->render('admin/wellness/sessions/index.html.twig', [
            'sessions' => $sessions,
        ]);
    }

    #[Route('/sessions/{id}', name: 'admin_wellness_sessions_show')]
    public function afficherSession(SessionActivite $session): Response
    {
        return $this->render('admin/wellness/sessions/show.html.twig', [
            'session' => $session,
        ]);
    }

    // OPTIONNEL : L'admin peut modifier le statut d'une session (ex: marquer comme "complétée")
    #[Route('/sessions/{id}/modifier', name: 'admin_wellness_sessions_edit')]
    public function modifierSession(
        SessionActivite $session,
        Request $request,
        EntityManagerInterface $em,
        ActiviteBienEtreRepository $activiteRepo
    ): Response {
        if ($request->isMethod('POST')) {
            $activiteId = $request->request->get('activite_id');
            $activite = $activiteRepo->find($activiteId);
            $session->setActivite($activite);
            
            $session->setDateDebut(new \DateTime($request->request->get('date_debut')));
            
            $dateFin = $request->request->get('date_fin');
            if ($dateFin) {
                $session->setDateFin(new \DateTime($dateFin));
            }
            
            $session->setDureeReelle($request->request->get('duree_reelle'));
            
            $statutSession = $request->request->get('statut_session');
            if ($statutSession) {
                $session->setStatutSession(StatutSession::from($statutSession));
            }
            
            $humeurAvant = $request->request->get('humeur_avant');
            if ($humeurAvant) {
                $session->setHumeurAvant(HumeurEnum::from($humeurAvant));
            }
            
            $session->setScoreHumeurAvant($request->request->get('score_humeur_avant'));
            $session->setEmotionAvant($request->request->get('emotion_avant'));
            
            $humeurApres = $request->request->get('humeur_apres');
            if ($humeurApres) {
                $session->setHumeurApres(HumeurEnum::from($humeurApres));
            }
            
            $session->setScoreHumeurApres($request->request->get('score_humeur_apres'));
            $session->setEmotionApres($request->request->get('emotion_apres'));
            $session->setNoteSatisfaction($request->request->get('note_satisfaction'));
            $session->setCommentaire($request->request->get('commentaire'));
            
            $impactPercu = $request->request->get('impact_percu');
            if ($impactPercu) {
                $session->setImpactPercu(ImpactPercu::from($impactPercu));
            }
            
            $session->setEstObjectifAtteint($request->request->get('est_objectif_atteint') === '1');
            
            $frequenceSouhaitee = $request->request->get('frequence_souhaitee');
            if ($frequenceSouhaitee) {
                $session->setFrequenceSouhaitee(FrequenceSouhaitee::from($frequenceSouhaitee));
            }
            
            $recommandePar = $request->request->get('recommande_par');
            if ($recommandePar) {
                $session->setRecommandePar(RecommandePar::from($recommandePar));
            }
            
            $session->setScoreRecommandationIa($request->request->get('score_recommandation_ia'));

            $em->flush();

            $this->addFlash('success', 'Session modifiée avec succès !');
            return $this->redirectToRoute('admin_wellness_sessions');
        }

        $activites = $activiteRepo->findAllActive();

        return $this->render('admin/wellness/sessions/edit.html.twig', [
            'session' => $session,
            'activites' => $activites,
            'statutsSessions' => StatutSession::cases(),
            'humeurs' => HumeurEnum::cases(),
            'impactsPercus' => ImpactPercu::cases(),
            'frequencesSouhaitees' => FrequenceSouhaitee::cases(),
            'recommandePar' => RecommandePar::cases(),
        ]);
    }

    // OPTIONNEL : L'admin peut supprimer une session
    #[Route('/sessions/{id}/supprimer', name: 'admin_wellness_sessions_delete', methods: ['POST'])]
    public function supprimerSession(
        SessionActivite $session,
        EntityManagerInterface $em
    ): Response {
        $em->remove($session);
        $em->flush();

        $this->addFlash('success', 'Session supprimée avec succès !');
        return $this->redirectToRoute('admin_wellness_sessions');
    }
}