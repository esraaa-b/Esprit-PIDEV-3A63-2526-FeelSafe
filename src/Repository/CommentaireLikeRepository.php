<?php

namespace App\Repository;

use App\Entity\CommentaireLike;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommentaireLike>
 */
class CommentaireLikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommentaireLike::class);
    }

    /**
     * Find a user's vote on a specific comment.
     */
    public function findByUserAndCommentaire(int $userId, int $commentaireId): ?CommentaireLike
    {
        // Use setMaxResults(1) + getResult() to avoid NonUniqueResultException
        // when legacy duplicate rows exist (old code allowed both like+dislike simultaneously)
        $results = $this->createQueryBuilder('cl')
            ->andWhere('cl.user = :userId')
            ->andWhere('cl.commentaire = :commentaireId')
            ->setParameter('userId', $userId)
            ->setParameter('commentaireId', $commentaireId)
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        return $results[0] ?? null;
    }

    /**
     * Delete all votes for a user on a comment (to clean up legacy duplicate rows).
     */
    public function deleteAllForUserAndCommentaire(int $userId, int $commentaireId): void
    {
        $this->createQueryBuilder('cl')
            ->delete()
            ->andWhere('cl.user = :userId')
            ->andWhere('cl.commentaire = :commentaireId')
            ->setParameter('userId', $userId)
            ->setParameter('commentaireId', $commentaireId)
            ->getQuery()
            ->execute();
    }

    public function countByCommentaireAndType(int $commentaireId, string $type): int
    {
        return (int) $this->createQueryBuilder('cl')
            ->select('COUNT(cl.id)')
            ->andWhere('cl.commentaire = :commentaireId')
            ->andWhere('cl.type = :type')
            ->setParameter('commentaireId', $commentaireId)
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get all votes for a user on a publication's comments.
     * Returns array: [ commentaireId => 'like'|'dislike' ]
     */
    public function findUserVotesForPublication(int $userId, int $publicationId): array
    {
        $results = $this->createQueryBuilder('cl')
            ->select('IDENTITY(cl.commentaire) AS commentaireId', 'cl.type')
            ->join('cl.commentaire', 'c')
            ->andWhere('c.publication = :publicationId')
            ->andWhere('cl.user = :userId')
            ->setParameter('publicationId', $publicationId)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getArrayResult();

        $votes = [];
        foreach ($results as $row) {
            $votes[$row['commentaireId']] = $row['type'];
        }
        return $votes;
    }
}