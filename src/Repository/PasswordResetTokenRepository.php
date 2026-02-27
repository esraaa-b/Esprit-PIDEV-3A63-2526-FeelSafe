<?php

namespace App\Repository;

use App\Entity\PasswordResetToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Utilisateur;
/**
 * @extends ServiceEntityRepository<PasswordResetToken>
 */
class PasswordResetTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordResetToken::class);
    }

    /**
     * Trouver un token valide
     */
    public function findValidToken(string $token): ?PasswordResetToken
    {
        return $this->createQueryBuilder('prt')
            ->where('prt.token = :token')
            ->andWhere('prt.expiresAt > :now')
            ->andWhere('prt.isUsed = :isUsed')
            ->setParameter('token', $token)
            ->setParameter('now', new \DateTime())
            ->setParameter('isUsed', false)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Supprimer les tokens expirés (nettoyage automatique)
     */
    public function deleteExpiredTokens(): int
    {
        return $this->createQueryBuilder('prt')
            ->delete()
            ->where('prt.expiresAt < :now')
            ->orWhere('prt.isUsed = :isUsed')
            ->setParameter('now', new \DateTime('-1 day'))
            ->setParameter('isUsed', true)
            ->getQuery()
            ->execute();
    }
}




