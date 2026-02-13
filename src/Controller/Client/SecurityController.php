<?php

namespace App\Controller\Client;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class SecurityController extends BaseDashboardController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('/dashboard/security', name: 'app_security')]
    public function index(): Response
    {
        try {
            $user = $this->getCurrentUser();
            
            // Sessions factices (à remplacer par de vraies sessions si nécessaire)
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

            return $this->render('client/security/index.html.twig', [
                'user' => $user,
                'sessions' => $sessions,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors du chargement de la page.');
            return $this->redirectToRoute('app_dashboard');
        }
    }

    #[Route('/dashboard/security/password', name: 'app_security_password', methods: ['POST'])]
    public function changePassword(Request $request): Response
    {
        $user = $this->getCurrentUser();
        
        $currentPassword = $request->request->get('current_password', '');
        $newPassword = $request->request->get('new_password', '');
        $confirmPassword = $request->request->get('confirm_password', '');

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('change-password', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_security');
        }

        // Vérifier que tous les champs sont remplis
        if (empty($currentPassword)) {
            $this->addFlash('error', 'Le champ "Mot de passe actuel" est obligatoire.');
            return $this->redirectToRoute('app_security');
        }
        
        if (empty($newPassword)) {
            $this->addFlash('error', 'Le champ "Nouveau mot de passe" est obligatoire.');
            return $this->redirectToRoute('app_security');
        }
        
        if (empty($confirmPassword)) {
            $this->addFlash('error', 'Le champ "Confirmer le nouveau mot de passe" est obligatoire.');
            return $this->redirectToRoute('app_security');
        }

        // Vérifier que le mot de passe actuel est correct
        if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
            $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
            return $this->redirectToRoute('app_security');
        }

        // Vérifier que les nouveaux mots de passe correspondent
        if ($newPassword !== $confirmPassword) {
            $this->addFlash('error', 'Les nouveaux mots de passe ne correspondent pas.');
            return $this->redirectToRoute('app_security');
        }

        // Vérifier la longueur du mot de passe
        if (strlen($newPassword) < 6) {
            $this->addFlash('error', 'Le mot de passe doit contenir au moins 6 caractères.');
            return $this->redirectToRoute('app_security');
        }

        try {
            // Hasher et sauvegarder le nouveau mot de passe
            $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Votre mot de passe a été modifié avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors du changement de mot de passe: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_security');
    }

    #[Route('/dashboard/security/privacy', name: 'app_security_privacy', methods: ['POST'])]
    public function updatePrivacy(Request $request): Response
    {
        $user = $this->getCurrentUser();
        $confidentialite = $user->getConfidentialite();

        if (!$confidentialite) {
            $this->addFlash('error', 'Paramètres de confidentialité non trouvés.');
            return $this->redirectToRoute('app_security');
        }

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('update-privacy', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_security');
        }

        try {
            // Mettre à jour les paramètres
            $confidentialite->setPartageDonnees($request->request->get('partage_donnees') === 'on');
            $confidentialite->setNotificationsEmail($request->request->get('notifications_email') === 'on');
            
            $visibilite = $request->request->get('visibilite_profil') === 'on' ? 'public' : 'prive';
            $confidentialite->setVisibiliteProfil($visibilite);
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Vos paramètres de confidentialité ont été mis à jour.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la mise à jour de vos paramètres: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_security');
    }
}