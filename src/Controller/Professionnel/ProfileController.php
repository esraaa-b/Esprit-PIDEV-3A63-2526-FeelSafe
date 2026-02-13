<?php

namespace App\Controller\Professionnel;

use App\Entity\User;
use App\Service\UserStatsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PROFESSIONNEL')]
class ProfileController extends BaseDashboardController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserStatsService $statsService
    ) {}

    // ✅ ROUTE: /pro/dashboard/profile  (ajout du préfixe /pro)
    #[Route('/pro/dashboard/profile', name: 'app_pro_profile')]
    public function index(): Response
    {
        try {
            $user = $this->getCurrentUser();
            
            $stats = $this->statsService->getUserStats($user);
            $achievements = $this->statsService->getUserAchievements($user);
            $progress = $this->statsService->getProgressData($user);
            $detailedStats = $this->statsService->getDetailedStats($user);

            return $this->render('professionnel/profile/index.html.twig', [
                'user' => $user,
                'stats' => $stats,
                'achievements' => $achievements,
                'progress' => $progress,
                'detailed_stats' => $detailedStats,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors du chargement de votre profil: ' . $e->getMessage());
            return $this->redirectToRoute('app_pro_dashboard');
        }
    }

    // ✅ ROUTE: /pro/dashboard/profile/edit  (ajout du préfixe /pro)
    #[Route('/pro/dashboard/profile/edit', name: 'app_pro_profile_edit', methods: ['POST'])]
    public function edit(Request $request): Response
    {
        $user = $this->getCurrentUser();
        
        if (!$this->isCsrfTokenValid('profile-edit', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_pro_security');
        }

        try {
            $prenom    = trim($request->request->get('prenom', ''));
            $nom       = trim($request->request->get('nom', ''));
            $email     = trim($request->request->get('email', ''));
            $telephone = trim($request->request->get('telephone', ''));

            if (empty($prenom) || empty($nom) || empty($email)) {
                $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires.');
                return $this->redirectToRoute('app_pro_security');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', "L'adresse email n'est pas valide.");
                return $this->redirectToRoute('app_pro_security');
            }

            $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                $this->addFlash('error', 'Cet email est déjà utilisé par un autre utilisateur.');
                return $this->redirectToRoute('app_pro_security');
            }

            $user->setPrenom($prenom);
            $user->setNom($nom);
            $user->setEmail($email);
            $user->setTelephone($telephone ?: null);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_pro_security');
    }

    // ✅ ROUTE: /pro/dashboard/profile/delete  (ajout du préfixe /pro)
    #[Route('/pro/dashboard/profile/delete', name: 'app_pro_profile_delete', methods: ['POST'])]
    public function delete(Request $request): Response
    {
        $user = $this->getCurrentUser();
        
        if (!$this->isCsrfTokenValid('delete-account', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_pro_security');
        }

        try {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
            $request->getSession()->invalidate();
            $this->container->get('security.token_storage')->setToken(null);
            return $this->redirectToRoute('app_login');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue : ' . $e->getMessage());
            return $this->redirectToRoute('app_pro_security');
        }
    }

    // ✅ ROUTE: /pro/dashboard/profile/export  (ajout du préfixe /pro)
    #[Route('/pro/dashboard/profile/export', name: 'app_pro_profile_export')]
    public function exportData(): Response
    {
        try {
            $user = $this->getCurrentUser();
            
            $stats = $this->statsService->getUserStats($user);
            $achievements = $this->statsService->getUserAchievements($user);
            $progress = $this->statsService->getProgressData($user);
            
            $data = [
                'export_info' => [
                    'date' => (new \DateTime())->format('Y-m-d H:i:s'),
                    'version' => '1.0',
                    'application' => 'FeelSafe'
                ],
                'user_info' => [
                    'id' => $user->getId(),
                    'nom' => $user->getNom(),
                    'prenom' => $user->getPrenom(),
                    'email' => $user->getEmail(),
                    'telephone' => $user->getTelephone(),
                    'date_creation' => $user->getDateCreation()->format('Y-m-d H:i:s'),
                    'statut' => $user->getStatut(),
                    'roles' => $user->getRoles(),
                ],
                'statistics' => [
                    'stats' => $stats,
                    'progress' => $progress,
                ],
                'achievements' => array_filter($achievements, fn($a) => $a['earned']),
                'confidentialite' => $user->getConfidentialite() ? [
                    'partage_donnees' => $user->getConfidentialite()->isPartageDonnees(),
                    'notifications_email' => $user->getConfidentialite()->isNotificationsEmail(),
                    'visibilite_profil' => $user->getConfidentialite()->getVisibiliteProfil(),
                    'date_modification' => $user->getConfidentialite()->getDateModification()->format('Y-m-d H:i:s'),
                ] : null,
            ];

            $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            $response = new Response($jsonData);
            $response->headers->set('Content-Type', 'application/json; charset=utf-8');
            $response->headers->set('Content-Disposition', 'attachment; filename="mes-donnees-feelsafe-' . date('Y-m-d') . '.json"');
            
            return $response;
        } catch (\Exception $e) {
            $this->addFlash('error', "Une erreur est survenue lors de l'export de vos données.");
            return $this->redirectToRoute('app_pro_profile');
        }
    }
}