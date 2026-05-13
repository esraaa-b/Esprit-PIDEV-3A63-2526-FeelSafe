<?php

namespace App\Repository;

use App\Entity\Urgence;
use App\DTO\UrgenceStats;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Urgence>
 */
class UrgenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Urgence::class);
    }

    /**
     * Retourne les statistiques des types d'urgence avec DTO
     * 
     * @return UrgenceStats
     */
    public function countDistinctTypeUrgence(): UrgenceStats
    {
        $qb = $this->createQueryBuilder('u')
            ->select('NEW App\\DTO\\UrgenceStats(COUNT(DISTINCT u.typeUrgence), COUNT(u.id))');

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * Retourne les statistiques des statuts d'urgence avec DTO
     * 
     * @return UrgenceStats
     */
    public function countDistinctStatus(): UrgenceStats
    {
        $qb = $this->createQueryBuilder('u')
            ->select('NEW App\\DTO\\UrgenceStats(COUNT(DISTINCT u.statut), COUNT(u.id))');

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * Get crises for last 30 days (gravity >= 3)
     */
    public function findCrisesLast30Days(int $userId): array
    {
        $thirtyDaysAgo = new \DateTime('-30 days');

        return $this->createQueryBuilder('u')
            ->select('u.dateHeure, u.niveauGravite')
            ->andWhere('u.idUtilisateur = :userId')
            ->andWhere('u.niveauGravite >= 3')
            ->andWhere('u.dateHeure >= :thirtyDaysAgo')
            ->setParameter('userId', $userId)
            ->setParameter('thirtyDaysAgo', $thirtyDaysAgo)
            ->orderBy('u.dateHeure', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calculate current streak (days without crisis)
     */
    public function calculateStreak(int $userId): int
    {
        $lastCrisis = $this->createQueryBuilder('u')
            ->select('u.dateHeure')
            ->andWhere('u.idUtilisateur = :userId')
            ->andWhere('u.niveauGravite >= 3')
            ->orderBy('u.dateHeure', 'DESC')
            ->setMaxResults(1)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$lastCrisis) {
            return 0;
        }

        $lastCrisisDate = $lastCrisis['dateHeure'];
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        $lastCrisisDate->setTime(0, 0, 0);

        if ($lastCrisisDate == $today) {
            return 0;
        }

        return $today->diff($lastCrisisDate)->days;
    }

    /**
     * Get all urgences for a specific user (Java: afficherParUtilisateur)
     */
    public function findByUtilisateur(int $userId): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.idUtilisateur = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('u.dateHeure', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get non-traitees urgences (statut = 'en attente') - Java: getUrgencesNonTraitees
     */
    public function findNonTraitees(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.statut = :statut')
            ->setParameter('statut', Urgence::STATUT_EN_ATTENTE)
            ->orderBy('u.niveauGravite', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count critical urgences (niveauGravite = 5) - Java: countUrgencesCritiques
     */
    public function countCritiques(): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.niveauGravite = :gravity')
            ->setParameter('gravity', 5)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count non-traitees urgences - Java: countUrgencesNonTraitees
     */
    public function countNonTraitees(): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.statut = :statut')
            ->setParameter('statut', Urgence::STATUT_EN_ATTENTE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    //    /**
    //     * @return Urgence[] Returns an array of Urgence objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Urgence
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}