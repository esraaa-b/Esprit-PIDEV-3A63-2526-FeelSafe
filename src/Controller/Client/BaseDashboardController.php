<?php
// src/Controller/Client/BaseDashboardController.php

namespace App\Controller\Client;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

abstract class BaseDashboardController extends AbstractController
{
    /**
     * Retourne l'utilisateur depuis la SESSION (lecture seule, affichage).
     * N'utiliser que pour afficher des données, jamais pour modifier.
     */
    protected function getCurrentUser(): Utilisateur
    {
        $user = $this->getUser();

        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        return $user;
    }

    /**
     * Retourne l'utilisateur GÉRÉ par Doctrine (depuis la base de données).
     * TOUJOURS utiliser cette méthode avant flush(), remove(), ou toute modification.
     *
     * Sans ça, Doctrine ne détecte aucun changement car l'objet vient de la session
     * et n'est pas dans l'UnitOfWork → aucun UPDATE n'est exécuté.
     */
    protected function getManagedUser(EntityManagerInterface $entityManager): Utilisateur
    {
        $user = $this->getUser();

        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        // Recharger depuis Doctrine pour que l'entité soit dans l'UnitOfWork
        $managedUser = $entityManager->getRepository(Utilisateur::class)->find($user->getId());

        if (!$managedUser) {
            throw $this->createNotFoundException('Utilisateur introuvable en base de données.');
        }

        return $managedUser;
    }

    /**
     * Récupère les données de l'utilisateur sous forme de tableau
     */
    protected function getUserData(): array
    {
        $user = $this->getCurrentUser();

        return [
            'id'           => $user->getId(),
            'fullName'     => $user->getFullName(),
            'email'        => $user->getEmail(),
            'prenom'       => $user->getPrenom(),
            'nom'          => $user->getNom(),
            'telephone'    => $user->getTelephone(),
            'roles'        => $user->getRoles(),
            'dateCreation' => $user->getDateCreation(),
        ];
    }
}