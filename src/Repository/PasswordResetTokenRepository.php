<?php

namespace App\Repository;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
     * Trouve un token valide pour un utilisateur
     */
    public function findValidToken(string $token): ?PasswordResetToken
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.token = :token')
            ->andWhere('p.isUsed = :isUsed')
            ->andWhere('p.expiresAt > :now')
            ->setParameter('token', $token)
            ->setParameter('isUsed', false)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Invalide tous les tokens existants pour un utilisateur
     */
    public function invalidateUserTokens(User $user): void
    {
        $this->createQueryBuilder('p')
            ->update()
            ->set('p.isUsed', ':used')
            ->where('p.user = :user')
            ->andWhere('p.isUsed = :notUsed')
            ->setParameter('used', true)
            ->setParameter('notUsed', false)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    /**
     * Supprime les tokens expirés (à appeler via un cron job)
     */
    public function deleteExpiredTokens(): int
    {
        return $this->createQueryBuilder('p')
            ->delete()
            ->where('p.expiresAt < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }
}