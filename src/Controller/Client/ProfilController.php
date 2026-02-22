<?php

namespace App\Controller\Client;

use App\Entity\ConfidentialiteUtilisateur;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/client/profil', name: 'client_profil_')]
#[IsGranted('ROLE_USER')]
class ProfilController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        // Créer confidentialité si elle n'existe pas encore
        if (!$user->getConfidentialite()) {
            $conf = new ConfidentialiteUtilisateur();
            $conf->setUtilisateur($user);
            $user->setConfidentialite($conf);
            $em->persist($conf);
            $em->flush();
        }

        return $this->render('client/profil/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/update', name: 'update', methods: ['POST'])]
    public function update(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $nom    = trim($request->request->get('nom', ''));
        $prenom = trim($request->request->get('prenom', ''));
        $email  = trim($request->request->get('email', ''));
        $tel    = trim($request->request->get('telephone', ''));

        if (empty($nom) || empty($prenom) || empty($email)) {
            $this->addFlash('error', 'Les champs Nom, Prénom et Email sont obligatoires.');
            return $this->redirectToRoute('client_profil_index', ['tab' => 'profil']);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'Adresse email invalide.');
            return $this->redirectToRoute('client_profil_index', ['tab' => 'profil']);
        }

        $user->setNom($nom);
        $user->setPrenom($prenom);
        $user->setEmail($email);
        $user->setTelephone($tel ?: null);

        $em->flush();

        $this->addFlash('success', 'Profil mis à jour avec succès !');
        return $this->redirectToRoute('client_profil_index', ['tab' => 'profil']);
    }

    #[Route('/confidentialite', name: 'confidentialite', methods: ['POST'])]
    public function confidentialite(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $conf = $user->getConfidentialite();
        if (!$conf) {
            $conf = new ConfidentialiteUtilisateur();
            $conf->setUtilisateur($user);
            $user->setConfidentialite($conf);
            $em->persist($conf);
        }

        $conf->setPartageDonnees((bool) $request->request->get('partage_donnees', false));
        $conf->setNotificationsEmail((bool) $request->request->get('notifications_email', false));
        $conf->setVisibiliteProfil($request->request->get('visibilite_profil', 'prive'));

        $em->flush();

        $this->addFlash('success', 'Paramètres de confidentialité enregistrés.');
        return $this->redirectToRoute('client_profil_index', ['tab' => 'confidentialite']);
    }

    #[Route('/supprimer', name: 'supprimer', methods: ['POST'])]
    public function supprimer(
        Request $request,
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $confirmation = $request->request->get('confirmation', '');
        if ($confirmation !== 'SUPPRIMER') {
            $this->addFlash('error', 'Veuillez taper "SUPPRIMER" pour confirmer la suppression.');
            return $this->redirectToRoute('client_profil_index', ['tab' => 'confidentialite']);
        }

        // Invalider la session et le token de sécurité avant la suppression
        $tokenStorage->setToken(null);
        $request->getSession()->invalidate();

        $em->remove($user);
        $em->flush();

        return $this->redirectToRoute('app_login');
    }
}