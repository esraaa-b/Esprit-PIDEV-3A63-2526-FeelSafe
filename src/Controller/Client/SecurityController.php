<?php

namespace App\Controller\Client;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/client/securite', name: 'client_securite_')]
#[IsGranted('ROLE_USER')]
class SecurityController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(): Response
    {
        $user = $this->getUser();

        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('client/security/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/mot-de-passe', name: 'password', methods: ['POST'])]
    public function updatePassword(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $actuel    = $request->request->get('mot_de_passe_actuel', '');
        $nouveau   = $request->request->get('nouveau_mot_de_passe', '');
        $confirmer = $request->request->get('confirmer_mot_de_passe', '');

        if (!$hasher->isPasswordValid($user, $actuel)) {
            $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
            return $this->redirectToRoute('client_securite_index');
        }

        if (strlen($nouveau) < 8) {
            $this->addFlash('error', 'Le nouveau mot de passe doit contenir au moins 8 caractères.');
            return $this->redirectToRoute('client_securite_index');
        }

        if ($nouveau !== $confirmer) {
            $this->addFlash('error', 'Les deux nouveaux mots de passe ne correspondent pas.');
            return $this->redirectToRoute('client_securite_index');
        }

        $hashed = $hasher->hashPassword($user, $nouveau);
        $user->setMotDePasse($hashed);
        $em->flush();

        $this->addFlash('success', 'Mot de passe mis à jour avec succès !');
        return $this->redirectToRoute('client_securite_index');
    }
}