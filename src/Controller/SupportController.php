<?php

namespace App\Controller;

<<<<<<< HEAD
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SupportController extends AbstractController
{
    #[Route('/dashboard/support', name: 'app_support')]
    public function index(): Response
    {
        // Professionals data
        $professionals = [
            [
                'id' => 1,
                'name' => 'Dr. Sophie Martin',
                'specialty' => 'Psychologue clinicienne',
                'rating' => 4.9,
                'reviews' => 127,
                'nextAvailable' => 'Demain 14h00',
                'price' => '70',
                'image' => null,
                'online' => true,
            ],
            [
                'id' => 2,
                'name' => 'Dr. Pierre Dubois',
                'specialty' => 'Psychiatre',
                'rating' => 4.8,
                'reviews' => 89,
                'nextAvailable' => 'Vendredi 10h30',
                'price' => '90',
                'image' => null,
                'online' => false,
            ],
            [
                'id' => 3,
                'name' => 'Marie Laurent',
                'specialty' => 'Thérapeute familiale',
                'rating' => 4.7,
                'reviews' => 64,
                'nextAvailable' => 'Lundi 16h00',
                'price' => '65',
                'image' => null,
                'online' => true,
            ],
        ];

        // Upcoming appointments
        $upcomingAppointments = [
            [
                'id' => 1,
                'professional' => 'Dr. Sophie Martin',
                'type' => 'Consultation vidéo',
                'date' => 'Demain',
                'time' => '14h00 - 15h00',
                'status' => 'confirmed',
            ],
            [
                'id' => 2,
                'professional' => 'Marie Laurent',
                'type' => 'Séance en personne',
                'date' => '28 Janvier 2024',
                'time' => '16h00 - 17h00',
                'status' => 'pending',
            ],
        ];

        // Chat messages
        $chatMessages = [
            ['role' => 'assistant', 'content' => 'Bonjour ! Je suis votre assistant bien-être. Comment puis-je vous aider aujourd\'hui ?'],
            ['role' => 'user', 'content' => 'Je me sens stressé ces derniers jours'],
            ['role' => 'assistant', 'content' => 'Je comprends que le stress peut être difficile à gérer. Pouvez-vous me dire ce qui vous cause du stress en ce moment ? Cela m\'aidera à mieux vous accompagner.'],
        ];

        return $this->render('dashboard/support/index.html.twig', [
            'professionals' => $professionals,
            'upcomingAppointments' => $upcomingAppointments,
            'chatMessages' => $chatMessages,
        ]);
    }
}
=======
use App\Entity\RendezVous;
use App\Entity\Utilisateur;
use App\Entity\Accompagnement;
use App\Enum\ModeRendezVous;
use App\Enum\StatutRendezVous;
use App\Enum\ProchainRdv;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SupportController extends AbstractController
{
    #[Route('/dashboard/support', name: 'app_support', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        if ($user->isProfessionnel()) {
            $rdvs = $em->getRepository(RendezVous::class)->findBy(['professionnel' => $user], ['dateRdv' => 'DESC']);
            return $this->render('professionnel/support/index.html.twig', [
                'rdvs' => $rdvs,
                'statuts' => StatutRendezVous::cases(),
            ]);
        }

        $professionnels = $em->getRepository(Utilisateur::class)->findByRole('ROLE_PROFESSIONNEL');
        $rdvsClient = $em->getRepository(RendezVous::class)->findBy(['utilisateur' => $user], ['dateRdv' => 'DESC']);
        return $this->render('client/support/index.html.twig', [
            'professionnels' => $professionnels,
            'modes' => ModeRendezVous::cases(),
            'rdvs' => $rdvsClient,
        ]);
    }

    #[Route('/dashboard/support/accompagnement', name: 'app_support_accomp', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function accompIndex(EntityManagerInterface $em, Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        if ($user->isProfessionnel()) {
            $clientId = $request->query->get('client_id');
            $client = null;
            if ($clientId) {
                $client = $em->getRepository(Utilisateur::class)->find((int)$clientId);
            }
            if ($client) {
                $accompagnements = $em->createQuery(
                    'SELECT a FROM App\Entity\Accompagnement a JOIN a.rendezvous r WHERE r.professionnel = :pro AND a.utilisateur = :client ORDER BY a.id DESC'
                )->setParameter('pro', $user)->setParameter('client', $client)->getResult();
                $rdvs = $em->createQuery(
                    'SELECT r FROM App\Entity\RendezVous r WHERE r.professionnel = :pro AND r.utilisateur = :client ORDER BY r.dateRdv DESC, r.heureRdv DESC'
                )->setParameter('pro', $user)->setParameter('client', $client)->getResult();
            } else {
                $accompagnements = $em->createQuery(
                    'SELECT a FROM App\Entity\Accompagnement a JOIN a.rendezvous r WHERE r.professionnel = :pro ORDER BY a.id DESC'
                )->setParameter('pro', $user)->getResult();
                $rdvs = $em->createQuery(
                    'SELECT r FROM App\Entity\RendezVous r WHERE r.professionnel = :pro ORDER BY r.dateRdv DESC, r.heureRdv DESC'
                )->setParameter('pro', $user)->getResult();
            }

            return $this->render('professionnel/support/accompagnement.html.twig', [
                'accompagnements' => $accompagnements,
                'rdvs' => $rdvs,
                'prochainRdvOptions' => ProchainRdv::cases(),
                'selectedClient' => $client,
            ]);
        }

        $accompagnements = $em->getRepository(Accompagnement::class)->findBy(['utilisateur' => $user], ['id' => 'DESC']);

        return $this->render('client/support/accompagnement.html.twig', [
            'accompagnements' => $accompagnements,
        ]);
    }

    #[Route('/dashboard/support/stats', name: 'app_support_stats', methods: ['GET'])]
    #[IsGranted('ROLE_PROFESSIONNEL')]
    public function stats(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }
        $start = new \DateTimeImmutable(date('Y-m-01 00:00:00'));
        $end = new \DateTimeImmutable(date('Y-m-t 23:59:59'));
        $rdvs = $em->createQuery(
            'SELECT r FROM App\Entity\RendezVous r WHERE r.professionnel = :pro AND r.dateRdv BETWEEN :s AND :e'
        )->setParameter('pro', $user)->setParameter('s', new \DateTime($start->format('Y-m-d')))->setParameter('e', new \DateTime($end->format('Y-m-d')))->getResult();
        $total = count($rdvs);
        $planifie = 0; $honore = 0; $annule = 0; $non_honore = 0;
        foreach ($rdvs as $r) {
            $v = $r->getStatut()->value;
            if ($v === 'planifie') $planifie++;
            elseif ($v === 'honore') $honore++;
            elseif ($v === 'annule') $annule++;
            elseif ($v === 'non_honore') $non_honore++;
        }
        $accomp = $em->createQuery(
            'SELECT a FROM App\Entity\Accompagnement a JOIN a.rendezvous r WHERE r.professionnel = :pro'
        )->setParameter('pro', $user)->getResult();
        $prochainOui = 0; $prochainNon = 0; $priorites = [];
        foreach ($accomp as $a) {
            $a->getProchainRdv()->value === 'oui' ? $prochainOui++ : $prochainNon++;
            if ($a->getNiveauPriorite() !== null) $priorites[] = (int)$a->getNiveauPriorite();
        }
        $avgPriorite = count($priorites) ? array_sum($priorites) / count($priorites) : null;
        return $this->render('professionnel/support/stats.html.twig', [
            'total' => $total,
            'planifie' => $planifie,
            'honore' => $honore,
            'annule' => $annule,
            'non_honore' => $non_honore,
            'prochainOui' => $prochainOui,
            'prochainNon' => $prochainNon,
            'avgPriorite' => $avgPriorite,
            'month' => date('m/Y'),
        ]);
    }
    #[Route('/dashboard/support/create', name: 'support_create', methods: ['POST'])]
    #[IsGranted('ROLE_CLIENT')]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        $professionnelId = $request->request->get('professionnel_id');
        $date = $request->request->get('date');
        $time = $request->request->get('time');
        $mode = $request->request->get('mode');
        $localisation = $request->request->get('localisation');
        $commentaire = $request->request->get('commentaire');

        $professionnel = $em->getRepository(Utilisateur::class)->find($professionnelId);
        if (!$professionnel || !$professionnel->isProfessionnel()) {
            $this->addFlash('error', 'Professionnel invalide');
            return $this->redirectToRoute('app_support');
        }

        if (!$date || !$time) {
            $this->addFlash('error', 'Date et heure sont obligatoires');
            return $this->redirectToRoute('app_support');
        }

        try {
            $rdvDate = new \DateTimeImmutable($date . ' ' . $time);
            $now = new \DateTimeImmutable('now');
            if ($rdvDate <= $now) {
                $this->addFlash('error', 'La date et l\'heure doivent être futures');
                return $this->redirectToRoute('app_support');
            }
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Format de date/heure invalide');
            return $this->redirectToRoute('app_support');
        }

        try {
            $modeEnum = ModeRendezVous::from($mode);
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Mode invalide');
            return $this->redirectToRoute('app_support');
        }
        if ($modeEnum === ModeRendezVous::EN_PRESENTIEL && !$localisation) {
            $this->addFlash('error', 'La localisation est obligatoire pour le présentiel');
            return $this->redirectToRoute('app_support');
        }

        $exists = (int) $em->createQuery(
            'SELECT COUNT(r.id) FROM App\Entity\RendezVous r WHERE r.professionnel = :pro AND r.dateRdv = :d AND r.heureRdv = :h'
        )->setParameter('pro', $professionnel)
         ->setParameter('d', new \DateTime($date))
         ->setParameter('h', new \DateTime($time))
         ->getSingleScalarResult();
        if ($exists > 0) {
            $this->addFlash('error', 'Ce créneau est déjà réservé chez ce professionnel');
            return $this->redirectToRoute('app_support');
        }

        try {
            $rdv = new RendezVous();
            $rdv->setUtilisateur($user);
            $rdv->setProfessionnel($professionnel);
            $rdv->setDateRdv(new \DateTime($date));
            $rdv->setHeureRdv(new \DateTime($time));
            $rdv->setMode($modeEnum);
            $rdv->setLocalisation($localisation ?: null);
            $rdv->setStatut(StatutRendezVous::PLANIFIE);
            $rdv->setCommentaire($commentaire ?: null);
            $rdv->setDateCreation(new \DateTime());

            $em->persist($rdv);
            $em->flush();

            $this->addFlash('success', 'Rendez-vous créé avec succès');
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Erreur lors de la création du rendez-vous');
        }

        return $this->redirectToRoute('app_support');
    }

    #[Route('/dashboard/support/rdv/{id}/accept', name: 'support_rdv_accept', methods: ['POST'])]
    #[IsGranted('ROLE_PROFESSIONNEL')]
    public function accept(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        $rdv = $em->getRepository(RendezVous::class)->find($id);
        if (!$rdv || $rdv->getProfessionnel()?->getId() !== $user->getId()) {
            $this->addFlash('error', 'Rendez-vous introuvable');
            return $this->redirectToRoute('app_support');
        }

        $ancien = $rdv->getStatut()->value;
        $comment = $request->request->get('commentaire');
        $rdv->setCommentaire($comment ?: $rdv->getCommentaire());
        $rdv->setStatut(StatutRendezVous::PLANIFIE);

        $em->flush();
        try {
            $em->getConnection()->executeStatement(
                'INSERT INTO rendez_vous_decision (id_rendez_vous, id_professionnel, action, ancien_statut, nouveau_statut, commentaire, date_action) VALUES (?,?,?,?,?,?,NOW())',
                [$rdv->getId(), $user->getId(), 'accept', $ancien, $rdv->getStatut()->value, $comment]
            );
        } catch (\Throwable $e) {}
        $this->addFlash('success', 'Demande acceptée');
        return $this->redirectToRoute('app_support');
    }

    #[Route('/dashboard/support/rdv/{id}/refuse', name: 'support_rdv_refuse', methods: ['POST'])]
    #[IsGranted('ROLE_PROFESSIONNEL')]
    public function refuse(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        $rdv = $em->getRepository(RendezVous::class)->find($id);
        if (!$rdv || $rdv->getProfessionnel()?->getId() !== $user->getId()) {
            $this->addFlash('error', 'Rendez-vous introuvable');
            return $this->redirectToRoute('app_support');
        }

        $ancien = $rdv->getStatut()->value;
        $comment = $request->request->get('commentaire');
        if ($comment) {
            $rdv->setCommentaire($comment);
        }
        try {
            $em->getConnection()->executeStatement(
                'INSERT INTO rendez_vous_decision (id_rendez_vous, id_professionnel, action, ancien_statut, nouveau_statut, commentaire, date_action) VALUES (?,?,?,?,?,?,NOW())',
                [$rdv->getId(), $user->getId(), 'refuse', $ancien, 'annule', $comment]
            );
        } catch (\Throwable $e) {}
        $em->remove($rdv);
        $em->flush();
        $this->addFlash('success', 'Demande refusée et supprimée');
        return $this->redirectToRoute('app_support');
    }

    #[Route('/dashboard/support/rdv/{id}/update', name: 'support_rdv_update', methods: ['POST'])]
    #[IsGranted('ROLE_PROFESSIONNEL')]
    public function update(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        $rdv = $em->getRepository(RendezVous::class)->find($id);
        if (!$rdv || $rdv->getProfessionnel()?->getId() !== $user->getId()) {
            $this->addFlash('error', 'Rendez-vous introuvable');
            return $this->redirectToRoute('app_support');
        }

        $statut = $request->request->get('statut');
        $comment = $request->request->get('commentaire');

        if ($statut) {
            $rdv->setStatut(StatutRendezVous::from($statut));
        }
        if ($comment !== null) {
            $rdv->setCommentaire($comment);
        }

        $em->flush();
        try {
            $em->getConnection()->executeStatement(
                'INSERT INTO rendez_vous_decision (id_rendez_vous, id_professionnel, action, ancien_statut, nouveau_statut, commentaire, date_action) VALUES (?,?,?,?,?,?,NOW())',
                [$rdv->getId(), $user->getId(), 'update', $rdv->getStatut()->value, $rdv->getStatut()->value, $comment]
            );
        } catch (\Throwable $e) {}
        $this->addFlash('success', 'Rendez-vous mis à jour');
        return $this->redirectToRoute('app_support');
    }

    #[Route('/dashboard/support/accompagnement/save', name: 'support_accomp_save', methods: ['POST'])]
    #[IsGranted('ROLE_PROFESSIONNEL')]
    public function accompSave(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        $rdvId = (int) $request->request->get('rendezvous_id');
        $rdv = $em->getRepository(RendezVous::class)->find($rdvId);
        if (!$rdv || $rdv->getProfessionnel()?->getId() !== $user->getId()) {
            $this->addFlash('error', 'Rendez-vous invalide');
            return $this->redirectToRoute('app_support_accomp');
        }

        $prochain = $request->request->get('prochain_rdv');
        $dateProchain = $request->request->get('date_prochain_rdv');
        $objectifs = $request->request->get('objectifs');
        $notes = $request->request->get('notes_suivi');
        $priorite = $request->request->get('niveau_priorite');

        $accompRepo = $em->getRepository(Accompagnement::class);
        $existing = $em->createQuery(
            'SELECT a FROM App\Entity\Accompagnement a WHERE a.rendezvous = :rdv AND a.utilisateur = :client'
        )->setParameter('rdv', $rdv)->setParameter('client', $rdv->getUtilisateur())->getOneOrNullResult();

        $accomp = $existing ?: new Accompagnement();
        $accomp->setRendezvous($rdv);
        $accomp->setUtilisateur($rdv->getUtilisateur());
        $accomp->setProchainRdv(ProchainRdv::from($prochain ?? 'non'));
        if ($accomp->getProchainRdv() === ProchainRdv::NON && $dateProchain) {
            $this->addFlash('error', 'Vous ne pouvez pas définir une date si prochain RDV = non');
            return $this->redirectToRoute('app_support_accomp');
        }
        if ($accomp->getProchainRdv() === ProchainRdv::OUI && !$dateProchain) {
            $this->addFlash('error', 'La date du prochain RDV est obligatoire');
            return $this->redirectToRoute('app_support_accomp');
        }
        if ($dateProchain) {
            try {
                $accomp->setDateProchainRdv(new \DateTime($dateProchain));
            } catch (\Throwable $e) {
                $this->addFlash('error', 'Date prochain RDV invalide');
                return $this->redirectToRoute('app_support_accomp');
            }
        } else {
            $accomp->setDateProchainRdv(null);
        }
        $cleanObjectifs = $objectifs ? substr(trim(strip_tags($objectifs)), 0, 255) : null;
        $cleanNotes = $notes ? substr(trim(strip_tags($notes)), 0, 255) : null;
        $accomp->setObjectifs($cleanObjectifs);
        $accomp->setNotesSuivi($cleanNotes);
        if ($priorite) {
            $p = max(1, min(5, (int) $priorite));
            $accomp->setNiveauPriorite($p);
        } else {
            $accomp->setNiveauPriorite(null);
        }

        if (!$existing) {
            $em->persist($accomp);
        }
        $em->flush();

        $this->addFlash('success', 'Accompagnement enregistré');
        return $this->redirectToRoute('app_support_accomp');
    }

    #[Route('/dashboard/support/rdv/{id}/ics', name: 'support_rdv_ics', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function exportIcs(int $id, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }
        $rdv = $em->getRepository(RendezVous::class)->find($id);
        if (!$rdv) {
            return new Response('Not Found', 404);
        }
        if ($rdv->getUtilisateur()?->getId() !== $user->getId() && $rdv->getProfessionnel()?->getId() !== $user->getId()) {
            return new Response('Forbidden', 403);
        }
        $start = new \DateTimeImmutable($rdv->getDateRdv()->format('Y-m-d') . ' ' . $rdv->getHeureRdv()->format('H:i'));
        $end = $start->modify('+1 hour');
        $uid = 'rdv-' . $rdv->getId() . '@feelsafe';
        $summary = 'Rendez-vous';
        $desc = ($rdv->getCommentaire() ?: '') . ($rdv->getLocalisation() ? ' - ' . $rdv->getLocalisation() : '');
        $loc = $rdv->getLocalisation() ?: '';
        $ics =
            "BEGIN:VCALENDAR\r\n" .
            "VERSION:2.0\r\n" .
            "PRODID:-//FeelSafe//RDV//FR\r\n" .
            "BEGIN:VEVENT\r\n" .
            "UID:$uid\r\n" .
            "DTSTAMP:" . $start->format('Ymd\THis\Z') . "\r\n" .
            "DTSTART:" . $start->format('Ymd\THis\Z') . "\r\n" .
            "DTEND:" . $end->format('Ymd\THis\Z') . "\r\n" .
            "SUMMARY:" . str_replace(["\r","\n"], '', $summary) . "\r\n" .
            "DESCRIPTION:" . str_replace(["\r","\n"], '', $desc) . "\r\n" .
            "LOCATION:" . str_replace(["\r","\n"], '', $loc) . "\r\n" .
            "BEGIN:VALARM\r\n" .
            "TRIGGER:-P1D\r\n" .
            "ACTION:DISPLAY\r\n" .
            "DESCRIPTION:Rappel J-1\r\n" .
            "END:VALARM\r\n" .
            "BEGIN:VALARM\r\n" .
            "TRIGGER:-PT2H\r\n" .
            "ACTION:DISPLAY\r\n" .
            "DESCRIPTION:Rappel H-2\r\n" .
            "END:VALARM\r\n" .
            "END:VEVENT\r\n" .
            "END:VCALENDAR\r\n";
        $response = new Response($ics);
        $response->headers->set('Content-Type', 'text/calendar; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="rdv-' . $rdv->getId() . '.ics"');
        return $response;
    }

    #[Route('/dashboard/support/accompagnement/upload', name: 'support_accomp_upload', methods: ['POST'])]
    #[IsGranted('ROLE_PROFESSIONNEL')]
    public function accompUpload(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }
        $rdvId = (int) $request->request->get('rendezvous_id');
        $rdv = $em->getRepository(RendezVous::class)->find($rdvId);
        if (!$rdv || $rdv->getProfessionnel()?->getId() !== $user->getId()) {
            $this->addFlash('error', 'Rendez-vous invalide');
            return $this->redirectToRoute('app_support_accomp');
        }
        $file = $request->files->get('piece');
        if (!$file) {
            $this->addFlash('error', 'Aucun fichier');
            return $this->redirectToRoute('app_support_accomp');
        }
        $safeName = 'rdv_' . $rdv->getId() . '_' . uniqid() . '.' . $file->guessExtension();
        $targetDir = dirname(__DIR__, 2) . '/public/uploads/accompagnement/' . $rdv->getId();
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0775, true);
        }
        $file->move($targetDir, $safeName);
        $this->addFlash('success', 'Document ajouté');
        return $this->redirectToRoute('app_support_accomp');
    }
}
>>>>>>> 3e7fee3 (Ajout des modifications module accompagnement et rendez-vous)
