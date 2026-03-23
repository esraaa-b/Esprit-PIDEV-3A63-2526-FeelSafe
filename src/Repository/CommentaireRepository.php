<?php

namespace App\Repository;

use App\Entity\Commentaire;
use App\Entity\Publication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commentaire>
 */
class CommentaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commentaire::class);
    }

    /**
     * Find comments for a publication, ordered by date.
     * setMaxResults avoids the "ORDER BY without LIMIT" Doctrine Doctor warning.
     *
     * @return Commentaire[]
     */
    public function findRootByPublication(Publication $publication, int $limit = 200): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.publication = :pub')
            ->setParameter('pub', $publication)
            ->orderBy('c.dateCommentaire', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
