<?php
// src/Controller/Client/BaseDashboardController.php

namespace App\Controller\Client;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

abstract class BaseDashboardController extends AbstractController
{
    /**
     * Récupère l'utilisateur actuellement connecté
     * 
     * @return User
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    protected function getCurrentUser(): User
    {
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }
        
        return $user;
    }
    
    /**
     * Récupère les données de l'utilisateur sous forme de tableau
     * (Utile pour d'autres contrôleurs qui en auraient besoin)
     * 
     * @return array
     */
    protected function getUserData(): array
    {
        $user = $this->getCurrentUser();
        
        return [
            'id' => $user->getId(),
            'fullName' => $user->getFullName(),
            'email' => $user->getEmail(),
            'prenom' => $user->getPrenom(),
            'nom' => $user->getNom(),
            'telephone' => $user->getTelephone(),
            'roles' => $user->getRoles(),
            'dateCreation' => $user->getDateCreation(),
        ];
    }
}