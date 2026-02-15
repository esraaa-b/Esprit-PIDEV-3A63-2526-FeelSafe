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
     * Find root comments (no parent) for a publication, ordered by date DESC.
     *
     * @return Commentaire[]
     */
    public function findRootByPublication(Publication $publication): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.publication = :pub')
            ->andWhere('c.parent IS NULL')
            ->setParameter('pub', $publication)
            ->orderBy('c.dateCommentaire', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
