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
            ->select('NEW App\\DTO\\UrgenceStats(COUNT(DISTINCT u.status), COUNT(u.id))');

        return $qb->getQuery()->getSingleResult();
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