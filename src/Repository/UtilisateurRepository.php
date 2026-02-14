<?php

namespace App\Repository;

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

/**
 * @extends ServiceEntityRepository<Utilisateur>
 */
class UtilisateurRepository extends ServiceEntityRepository
{
   public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Utilisateur) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setMotDePasse($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Trouver tous les utilisateurs ordonnés par date de création
     */
    public function findAll(): array
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les utilisateurs par rôle
     */
    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%"' . $role . '"%')
            ->orderBy('u.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les utilisateurs actifs
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.statut = :statut')
            ->setParameter('statut', 'actif')
            ->orderBy('u.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compter les utilisateurs par rôle
     */
    public function countByRole(string $role): int
    {
        try {
            return (int) $this->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->where('u.roles LIKE :role')
                ->setParameter('role', '%"' . $role . '"%')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Rechercher des utilisateurs par nom, prénom ou email
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.nom LIKE :query')
            ->orWhere('u.prenom LIKE :query')
            ->orWhere('u.email LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('u.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtenir les derniers utilisateurs inscrits
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.dateCreation', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtenir les statistiques des utilisateurs
     */
    public function getStatistics(): array
    {
        $total = $this->count([]);
        
        return [
            'total' => $total,
            'clients' => $this->countByRole('ROLE_CLIENT'),
            'professionnels' => $this->countByRole('ROLE_PROFESSIONNEL'),
            'admins' => $this->countByRole('ROLE_ADMIN'),
            'actifs' => $this->count(['statut' => 'actif']),
            'inactifs' => $this->count(['statut' => 'inactif']),
        ];
    }
}