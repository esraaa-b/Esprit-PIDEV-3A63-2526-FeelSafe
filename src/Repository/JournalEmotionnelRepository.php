<?php

namespace App\Repository;

use App\Entity\JournalEmotionnel;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JournalEmotionnel>
 */
class JournalEmotionnelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JournalEmotionnel::class);
    }

    /**
     * Fetch journals for a user within a date range.
     * Uses JOIN FETCH on utilisateur to avoid N+1 queries.
     *
     * @return JournalEmotionnel[]
     */
    public function findByUserAndDateRange(
        Utilisateur $user,
        \DateTimeInterface $start,
        \DateTimeInterface $end
    ): array {
        return $this->createQueryBuilder('j')
            ->addSelect('u')
            ->join('j.utilisateur', 'u')
            ->where('j.utilisateur = :user')
            ->andWhere('j.dateCreation >= :start')
            ->andWhere('j.dateCreation < :end')
            ->setParameter('user', $user)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('j.dateCreation', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Fetch recent journals for a user with a LIMIT.
     * Avoids ORDER BY without LIMIT Doctrine Doctor warning.
     *
     * @return JournalEmotionnel[]
     */
    public function findRecentByUser(Utilisateur $user, int $limit = 50): array
    {
        return $this->createQueryBuilder('j')
            ->where('j.utilisateur = :user')
            ->setParameter('user', $user)
            ->orderBy('j.dateCreation', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
