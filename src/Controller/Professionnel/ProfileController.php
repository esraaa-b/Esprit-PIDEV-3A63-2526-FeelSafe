<?php

namespace App\Controller\Professionnel;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProfileController extends BaseDashboardController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('/pro/profile', name: 'app_pro_profile')]
    #[IsGranted('ROLE_PROFESSIONNEL')]
    public function index(): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        
        // Statistiques pour le professionnel (vous pouvez les calculer dynamiquement)
        $stats = [
            'rendezvous' => 24,
            'patients' => 12,
            'journaux' => 48,
            'note' => 4.8,
        ];

        return $this->render('professionnel/security/index.html.twig', array_merge(
            $this->getUserData(),
            ['stats' => $stats]
        ));
    }

    #[Route('/pro/profile/edit', name: 'app_pro_profile_edit', methods: ['POST'])]
#[IsGranted('ROLE_PROFESSIONNEL')]
public function edit(Request $request): Response
{
    /** @var Utilisateur $user */
    $user = $this->getUser();

    if (!$this->isCsrfTokenValid('profile-edit', $request->request->get('_token'))) {
        $this->addFlash('error', 'Token de sécurité invalide.');
        return $this->redirectToRoute('app_pro_profile');
    }

    $user->setPrenom($request->request->get('prenom'));
    $user->setNom($request->request->get('nom'));
    $user->setEmail($request->request->get('email'));
    $user->setTelephone($request->request->get('telephone'));

    $this->entityManager->flush();

    $this->addFlash('success', 'Profil mis à jour avec succès.');
    return $this->redirectToRoute('app_pro_profile');
}

    #[Route('/pro/profile/export', name: 'app_pro_profile_export')]
    #[IsGranted('ROLE_PROFESSIONNEL')]
    public function export(): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        // Création des données à exporter
        $data = [
            'Informations personnelles' => [
                'Prénom' => $user->getPrenom(),
                'Nom' => $user->getNom(),
                'Email' => $user->getEmail(),
                'Téléphone' => $user->getTelephone() ?? 'Non renseigné',
                'Membre depuis' => $user->getDateCreation()->format('d/m/Y'),
            ],
            'Statistiques' => [
                'Rendez-vous' => 24, // À calculer dynamiquement
                'Patients' => 12,     // À calculer dynamiquement
                'Journaux' => 48,      // À calculer dynamiquement
            ]
        ];

        // Conversion en JSON
        $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Création de la réponse avec le fichier JSON
        $response = new Response($jsonData);
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Content-Disposition', 'attachment; filename="mon_profil.json"');

        $this->addFlash('success', 'Export réussi.');

        return $response;
    }

    #[Route('/pro/profile/delete', name: 'app_pro_profile_delete', methods: ['POST'])]
    #[IsGranted('ROLE_PROFESSIONNEL')]
    public function delete(Request $request): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('delete-account', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_pro_profile');
        }

        $password = $request->request->get('password');

        // Vérification du mot de passe
        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            $this->addFlash('error', 'Mot de passe incorrect.');
            return $this->redirectToRoute('app_pro_profile');
        }

        try {
            // Déconnexion de l'utilisateur avant suppression
            $this->container->get('security.token_storage')->setToken(null);
            
            // Suppression de l'utilisateur
            $this->entityManager->remove($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre compte a été supprimé avec succès.');
            
            return $this->redirectToRoute('app_login');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression de votre compte.');
            return $this->redirectToRoute('app_pro_profile');
        }
    }
}