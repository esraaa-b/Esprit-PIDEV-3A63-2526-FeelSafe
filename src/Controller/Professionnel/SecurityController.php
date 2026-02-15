<?php

namespace App\Controller\Professionnel;

use App\Entity\Utilisateur;
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

   #[Route('/pro/security', name: 'app_pro_security')]
public function index(): Response
{
    // SUPPRIMEZ le try/catch pour voir l'erreur
    /** @var Utilisateur $user */
    $user = $this->getCurrentUser();
    
    // Décommentez pour debug
    // dump($user); die();
    
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
}

   #[Route('/pro/security/password', name: 'app_pro_security_password', methods: ['POST'])]
public function changePassword(Request $request): Response
{
    /** @var Utilisateur $user */
    $user = $this->getCurrentUser();
    
    $currentPassword = $request->request->get('current_password', '');
    $newPassword = $request->request->get('new_password', '');
    $confirmPassword = $request->request->get('confirm_password', '');

    if (!$this->isCsrfTokenValid('change-password', $request->request->get('_token'))) {
        $this->addFlash('error', 'Token de sécurité invalide.');
        return $this->redirectToRoute('app_pro_security');
    }

    // Si le champ current_password est vide, on ne vérifie pas l'ancien mot de passe
    if (empty($currentPassword)) {
        $this->addFlash('error', 'Veuillez entrer votre mot de passe actuel.');
        return $this->redirectToRoute('app_pro_security');
    }

    if (empty($newPassword) || empty($confirmPassword)) {
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
        $user->setMotDePasse($hashedPassword);
        
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Votre mot de passe a été modifié avec succès.');
    } catch (\Exception $e) {
        $this->addFlash('error', 'Une erreur est survenue lors du changement de mot de passe.');
    }

    return $this->redirectToRoute('app_pro_security');
}

    #[Route('/pro/security/privacy', name: 'app_pro_security_privacy', methods: ['POST'])]
    public function updatePrivacy(Request $request): Response
    {
        /** @var Utilisateur $user */
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
}