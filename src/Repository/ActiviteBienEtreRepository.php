<?php

namespace App\Repository;

use App\Entity\ActiviteBienEtre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActiviteBienEtre>
 */
class ActiviteBienEtreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActiviteBienEtre::class);
    }

    /**
     * Récupère toutes les activités actives
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.estActive = :active')
            ->setParameter('active', true)
            ->orderBy('a.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les activités par type
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.typeActivite = :type')
            ->andWhere('a.estActive = :active')
            ->setParameter('type', $type)
            ->setParameter('active', true)
            ->orderBy('a.nomActivite', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les activités par catégorie
     */
    public function findByCategorie(string $categorie): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.categorie = :categorie')
            ->andWhere('a.estActive = :active')
            ->setParameter('categorie', $categorie)
            ->setParameter('active', true)
            ->orderBy('a.nomActivite', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche d'activités par nom ou description
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.nomActivite LIKE :query')
            ->orWhere('a.description LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('a.nomActivite', 'ASC')
            ->getQuery()
            ->getResult();
    }

/**
 * Statistiques des activités
 */
public function getStatistics(): array
{
    // Total
    $total = $this->createQueryBuilder('a')
        ->select('COUNT(a.id)')
        ->getQuery()
        ->getSingleScalarResult();
    
    // Actives
    $actives = $this->createQueryBuilder('a')
        ->select('COUNT(a.id)')
        ->where('a.estActive = :active')
        ->setParameter('active', true)
        ->getQuery()
        ->getSingleScalarResult();
    
    // Prédéfinies
    $predefinies = $this->createQueryBuilder('a')
        ->select('COUNT(a.id)')
        ->where('a.estPredefinie = :predef')
        ->setParameter('predef', true)
        ->getQuery()
        ->getSingleScalarResult();
    
    return [
        'total' => $total,
        'actives' => $actives,
        'predefinies' => $predefinies,
    ];
}
}