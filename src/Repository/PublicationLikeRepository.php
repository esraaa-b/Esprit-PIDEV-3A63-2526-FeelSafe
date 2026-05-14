<?php

namespace App\Repository;

use App\Entity\PublicationLike;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PublicationLike>
 */
class PublicationLikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PublicationLike::class);
    }

    public function findByUserAndPublication(int $userId, int $publicationId): ?PublicationLike
    {
        // Use setMaxResults(1) + getResult() to avoid NonUniqueResultException
        // when legacy duplicate rows exist (old code allowed both like+dislike simultaneously)
        $results = $this->createQueryBuilder('pl')
            ->andWhere('pl.user = :userId')
            ->andWhere('pl.publication = :publicationId')
            ->setParameter('userId', $userId)
            ->setParameter('publicationId', $publicationId)
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        return $results[0] ?? null;
    }

    /**
     * Delete all votes for a user on a publication, then re-insert the given type.
     * Used to clean up duplicate legacy rows.
     */
    public function deleteAllForUserAndPublication(int $userId, int $publicationId): void
    {
        $this->createQueryBuilder('pl')
            ->delete()
            ->andWhere('pl.user = :userId')
            ->andWhere('pl.publication = :publicationId')
            ->setParameter('userId', $userId)
            ->setParameter('publicationId', $publicationId)
            ->getQuery()
            ->execute();
    }

    public function countByPublicationAndType(int $publicationId, string $type): int
    {
        return (int) $this->createQueryBuilder('pl')
            ->select('COUNT(pl.id)')
            ->andWhere('pl.publication = :publicationId')
            ->andWhere('pl.type = :type')
            ->setParameter('publicationId', $publicationId)
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get all votes for a user on a list of publications.
     * Returns array: [ publicationId => 'like'|'dislike' ]
     */
    public function findUserVotesForPublications(int $userId, array $publications): array
    {
        if (empty($publications)) return [];
       
        $ids = array_map(fn($p) => $p->getId(), $publications);

        $results = $this->createQueryBuilder('pl')
            ->select('IDENTITY(pl.publication) AS publicationId', 'pl.type')
            ->andWhere('pl.publication IN (:ids)')
            ->andWhere('pl.user = :userId')
            ->setParameter('ids', $ids)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getArrayResult();

        $votes = [];
        foreach ($results as $row) {
            $votes[$row['publicationId']] = $row['type'];
        }
        return $votes;
    }

    public function findUserVotesForPublication(int $userId, int $publicationId): array
    {
        $vote = $this->findByUserAndPublication($userId, $publicationId);
        if (!$vote) return [];
       
        return [$vote->getType() => true];
    }
}