<?php

namespace App\Controller\Professionnel;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PROFESSIONNEL')]
class SecurityController extends BaseDashboardController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    // ⚡ CHANGEZ: app_security → app_pro_security
    #[Route('/pro/dashboard/security', name: 'app_pro_security')]
    public function index(): Response
    {
        try {
            $user = $this->getCurrentUser();
            
            $sessions = [
                [
                    'device' => 'Chrome - Windows',
                    'location' => 'Tunis, Tunisie',
                    'lastActive' => 'Maintenant',
                    'current' => true
                ],
                [
                    'device' => 'Safari - iPhone',
                    'location' => 'Tunis, Tunisie',
                    'lastActive' => 'Il y a 2h',
                    'current' => false
                ],
            ];

            return $this->render('professionnel/security/index.html.twig', [
                'user' => $user,
                'sessions' => $sessions,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors du chargement de la page.');
            return $this->redirectToRoute('app_dashboard');
        }
    }

    // ⚡ CHANGEZ: app_security_password → app_pro_security_password
    #[Route('/pro/dashboard/security/password', name: 'app_pro_security_password', methods: ['POST'])]
    public function changePassword(Request $request): Response
    {
        $user = $this->getCurrentUser();
        
        $currentPassword = $request->request->get('current_password', '');
        $newPassword = $request->request->get('new_password', '');
        $confirmPassword = $request->request->get('confirm_password', '');

        if (!$this->isCsrfTokenValid('change-password', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_pro_security');
        }

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $this->addFlash('error', 'Veuillez remplir tous les champs.');
            return $this->redirectToRoute('app_pro_security');
        }

        if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
            $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
            return $this->redirectToRoute('app_pro_security');
        }

        if ($newPassword !== $confirmPassword) {
            $this->addFlash('error', 'Les nouveaux mots de passe ne correspondent pas.');
            return $this->redirectToRoute('app_pro_security');
        }

        if (strlen($newPassword) < 6) {
            $this->addFlash('error', 'Le mot de passe doit contenir au moins 6 caractères.');
            return $this->redirectToRoute('app_pro_security');
        }

        try {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Votre mot de passe a été modifié avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors du changement de mot de passe.');
        }

        return $this->redirectToRoute('app_pro_security');
    }

    // ⚡ CHANGEZ: app_security_privacy → app_pro_security_privacy
    #[Route('/pro/dashboard/security/privacy', name: 'app_pro_security_privacy', methods: ['POST'])]
    public function updatePrivacy(Request $request): Response
    {
        $user = $this->getCurrentUser();
        $confidentialite = $user->getConfidentialite();

        if (!$confidentialite) {
            $this->addFlash('error', 'Paramètres de confidentialité non trouvés.');
            return $this->redirectToRoute('app_pro_security');
        }

        if (!$this->isCsrfTokenValid('update-privacy', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_pro_security');
        }

        try {
            $confidentialite->setPartageDonnees($request->request->get('partage_donnees') === 'on');
            $confidentialite->setNotificationsEmail($request->request->get('notifications_email') === 'on');
            
            $visibilite = $request->request->get('visibilite_profil') === 'on' ? 'public' : 'prive';
            $confidentialite->setVisibiliteProfil($visibilite);
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Vos paramètres de confidentialité ont été mis à jour.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la mise à jour de vos paramètres.');
        }

        return $this->redirectToRoute('app_pro_security');
    }

    // ⚡ AJOUTEZ ces routes manquantes
    #[Route('/pro/dashboard/profile/edit', name: 'app_pro_profile_edit', methods: ['POST'])]
    public function editProfile(Request $request): Response
    {
        $user = $this->getCurrentUser();

        if (!$this->isCsrfTokenValid('profile-edit', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_pro_security');
        }

        $prenom = trim($request->request->get('prenom', ''));
        $nom = trim($request->request->get('nom', ''));
        $email = trim($request->request->get('email', ''));
        $telephone = trim($request->request->get('telephone', ''));

        if (empty($prenom) || empty($nom) || empty($email)) {
            $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires.');
            return $this->redirectToRoute('app_pro_security');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'L\'adresse email n\'est pas valide.');
            return $this->redirectToRoute('app_pro_security');
        }

        try {
            $existingUser = $this->entityManager->getRepository(User::class)
                ->findOneBy(['email' => $email]);
            
            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                $this->addFlash('error', 'Cet email est déjà utilisé par un autre compte.');
                return $this->redirectToRoute('app_pro_security');
            }

            $user->setPrenom($prenom);
            $user->setNom($nom);
            $user->setEmail($email);
            $user->setTelephone($telephone);
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la mise à jour de votre profil.');
        }

        return $this->redirectToRoute('app_pro_security');
    }

    #[Route('/pro/dashboard/profile/delete', name: 'app_pro_profile_delete', methods: ['POST'])]
    public function deleteAccount(Request $request): Response
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
            
            $this->addFlash('success', 'Votre compte a été supprimé avec succès.');
            return $this->redirectToRoute('app_login');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression de votre compte.');
            return $this->redirectToRoute('app_pro_security');
        }
    }
}