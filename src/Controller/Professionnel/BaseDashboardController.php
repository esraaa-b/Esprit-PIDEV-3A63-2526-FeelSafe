<?php
// src/Controller/Professionnel/BaseDashboardController.php

namespace App\Controller\Professionnel;

use App\Entity\Utilisateur;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

abstract class BaseDashboardController extends AbstractController
{
    /**
     * Récupère l'utilisateur professionnel actuellement connecté
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
     * Récupère les données du professionnel sous forme de tableau
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