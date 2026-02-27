<?php

namespace App\Controller;

use App\Entity\ConfidentialiteUtilisateur;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class SecurityController extends AbstractController
{
    // ─── Affichage principal ───────────────────────────────────────────
    #[Route('/dashboard/security', name: 'app_security')]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        // Créer confidentialité si elle n'existe pas
        if (!$user->getConfidentialite()) {
            $conf = new ConfidentialiteUtilisateur();
            $conf->setUtilisateur($user);
            $user->setConfidentialite($conf);
            $em->persist($conf);
            $em->flush();
        }

        return $this->render('dashboard/security/index.html.twig', [
            'user' => $user,
        ]);
    }

    // ─── Modifier profil ───────────────────────────────────────────────
    #[Route('/dashboard/security/profil/update', name: 'app_security_profil_update', methods: ['POST'])]
    public function updateProfil(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        $nom    = trim($request->request->get('nom', ''));
        $prenom = trim($request->request->get('prenom', ''));
        $email  = trim($request->request->get('email', ''));
        $tel    = trim($request->request->get('telephone', ''));

        if (empty($nom) || empty($prenom) || empty($email)) {
            $this->addFlash('error_profil', 'Les champs Nom, Prénom et Email sont obligatoires.');
            return $this->redirectToRoute('app_security', ['tab' => 'profil']);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error_profil', 'Adresse email invalide.');
            return $this->redirectToRoute('app_security', ['tab' => 'profil']);
        }

        $user->setNom($nom);
        $user->setPrenom($prenom);
        $user->setEmail($email);
        $user->setTelephone($tel ?: null);
        $em->flush();

        $this->addFlash('success_profil', 'Profil mis à jour avec succès !');
        return $this->redirectToRoute('app_security', ['tab' => 'profil']);
    }

    // ─── Modifier mot de passe ─────────────────────────────────────────
    #[Route('/dashboard/security/password/update', name: 'app_security_password_update', methods: ['POST'])]
    public function updatePassword(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        $actuel    = $request->request->get('mot_de_passe_actuel', '');
        $nouveau   = $request->request->get('nouveau_mot_de_passe', '');
        $confirmer = $request->request->get('confirmer_mot_de_passe', '');

        if (!$hasher->isPasswordValid($user, $actuel)) {
            $this->addFlash('error_password', 'Le mot de passe actuel est incorrect.');
            return $this->redirectToRoute('app_security', ['tab' => 'securite']);
        }

        if (strlen($nouveau) < 8) {
            $this->addFlash('error_password', 'Le nouveau mot de passe doit contenir au moins 8 caractères.');
            return $this->redirectToRoute('app_security', ['tab' => 'securite']);
        }

        if ($nouveau !== $confirmer) {
            $this->addFlash('error_password', 'Les deux mots de passe ne correspondent pas.');
            return $this->redirectToRoute('app_security', ['tab' => 'securite']);
        }

        $user->setMotDePasse($hasher->hashPassword($user, $nouveau));
        $em->flush();

        $this->addFlash('success_password', 'Mot de passe mis à jour avec succès !');
        return $this->redirectToRoute('app_security', ['tab' => 'securite']);
    }

    // ─── Modifier confidentialité ──────────────────────────────────────
    #[Route('/dashboard/security/confidentialite/update', name: 'app_security_confidentialite_update', methods: ['POST'])]
    public function updateConfidentialite(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        $conf = $user->getConfidentialite();
        if (!$conf) {
            $conf = new ConfidentialiteUtilisateur();
            $conf->setUtilisateur($user);
            $user->setConfidentialite($conf);
            $em->persist($conf);
        }

        // Récupérer les valeurs du formulaire
        $partageDonnees = $request->request->get('partage_donnees') ? true : false;
        $notificationsEmail = $request->request->get('notifications_email') ? true : false;
        $visibiliteProfil = $request->request->get('visibilite_profil', 'prive');

        // Mettre à jour les valeurs
        $conf->setPartageDonnees($partageDonnees);
        $conf->setNotificationsEmail($notificationsEmail);
        $conf->setVisibiliteProfil($visibiliteProfil);
        
        // La date sera automatiquement mise à jour grâce à #[ORM\PreUpdate]
        
        $em->flush();

        $this->addFlash('success_conf', 'Paramètres de confidentialité enregistrés.');
        return $this->redirectToRoute('app_security', ['tab' => 'confidentialite']);
    }

    // ─── Supprimer le compte ───────────────────────────────────────────
    #[Route('/dashboard/security/supprimer', name: 'app_security_supprimer', methods: ['POST'])]
    public function supprimerCompte(
        Request $request,
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->request->get('confirmation') !== 'SUPPRIMER') {
            $this->addFlash('error_conf', 'Veuillez taper "SUPPRIMER" pour confirmer.');
            return $this->redirectToRoute('app_security', ['tab' => 'confidentialite']);
        }

        $tokenStorage->setToken(null);
        $request->getSession()->invalidate();
        $em->remove($user);
        $em->flush();

        return $this->redirectToRoute('app_login');
    }
}