<?php

namespace App\Repository;

use App\Entity\ConfidentialiteUtilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ConfidentialiteUtilisateur>
 */
class ConfidentialiteUtilisateurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConfidentialiteUtilisateur::class);
    }

    /**
     * Trouver les utilisateurs ayant accepté le partage de données
     */
    public function findWithDataSharing(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.partageDonnees = :partage')
            ->setParameter('partage', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les utilisateurs avec notifications email activées
     */
    public function findWithEmailNotifications(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.notificationsEmail = :notifications')
            ->setParameter('notifications', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver par type de visibilité
     */
    public function findByVisibility(string $visibility): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.visibiliteProfil = :visibility')
            ->setParameter('visibility', $visibility)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compter les utilisateurs par paramètre de confidentialité
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('c');
        
        return [
            'total' => $qb->select('COUNT(c.id)')->getQuery()->getSingleScalarResult(),
            'partage_donnees' => $this->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->andWhere('c.partageDonnees = true')
                ->getQuery()
                ->getSingleScalarResult(),
            'notifications_email' => $this->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->andWhere('c.notificationsEmail = true')
                ->getQuery()
                ->getSingleScalarResult(),
            'profil_public' => $this->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->andWhere('c.visibiliteProfil = :visibility')
                ->setParameter('visibility', 'public')
                ->getQuery()
                ->getSingleScalarResult(),
        ];
    }
}