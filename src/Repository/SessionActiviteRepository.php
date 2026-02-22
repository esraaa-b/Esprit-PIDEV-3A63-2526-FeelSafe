<?php

namespace App\Repository;

use App\Entity\SessionActivite;
use App\Entity\Utilisateur;
use App\Enum\StatutSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SessionActivite>
 */
class SessionActiviteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SessionActivite::class);
    }

    /**
     * Récupère toutes les sessions d'un utilisateur
     */
    public function findByUtilisateur(Utilisateur $utilisateur): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.utilisateur = :utilisateur')
            ->setParameter('utilisateur', $utilisateur)
            ->orderBy('s.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les sessions par statut
     */
    public function findByStatut(StatutSession $statut): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.statutSession = :statut')
            ->setParameter('statut', $statut)
            ->orderBy('s.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les sessions d'un utilisateur par statut
     */
    public function findByUtilisateurAndStatut(Utilisateur $utilisateur, StatutSession $statut): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.utilisateur = :utilisateur')
            ->andWhere('s.statutSession = :statut')
            ->setParameter('utilisateur', $utilisateur)
            ->setParameter('statut', $statut)
            ->orderBy('s.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les sessions complétées avec amélioration de l'humeur
     */
    public function findSessionsAvecAmelioration(Utilisateur $utilisateur): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.utilisateur = :utilisateur')
            ->andWhere('s.statutSession = :statut')
            ->andWhere('s.scoreHumeurApres > s.scoreHumeurAvant')
            ->setParameter('utilisateur', $utilisateur)
            ->setParameter('statut', StatutSession::COMPLETEE)
            ->orderBy('s.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

/**
 * Statistiques globales des sessions
 */
public function getStatistics(): array
{
    // Total
    $total = $this->createQueryBuilder('s')
        ->select('COUNT(s.id)')
        ->getQuery()
        ->getSingleScalarResult();
    
    // Complétées
    $completees = $this->createQueryBuilder('s')
        ->select('COUNT(s.id)')
        ->where('s.statutSession = :statut')
        ->setParameter('statut', StatutSession::COMPLETEE)
        ->getQuery()
        ->getSingleScalarResult();
    
    // En cours
    $enCours = $this->createQueryBuilder('s')
        ->select('COUNT(s.id)')
        ->where('s.statutSession = :statut')
        ->setParameter('statut', StatutSession::EN_COURS)
        ->getQuery()
        ->getSingleScalarResult();
    
    return [
        'total' => $total,
        'completees' => $completees,
        'en_cours' => $enCours,
    ];
}

    /**
     * Récupère les sessions récentes (7 derniers jours)
     */
    public function findRecent(int $days = 7): array
    {
        $date = new \DateTime();
        $date->modify("-{$days} days");

        return $this->createQueryBuilder('s')
            ->where('s.dateDebut >= :date')
            ->setParameter('date', $date)
            ->orderBy('s.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule la moyenne d'amélioration de l'humeur
     */
    public function getMoyenneAmeliorationHumeur(Utilisateur $utilisateur): ?float
    {
        $result = $this->createQueryBuilder('s')
            ->select('AVG(s.scoreHumeurApres - s.scoreHumeurAvant) as moyenne')
            ->where('s.utilisateur = :utilisateur')
            ->andWhere('s.statutSession = :statut')
            ->andWhere('s.scoreHumeurAvant IS NOT NULL')
            ->andWhere('s.scoreHumeurApres IS NOT NULL')
            ->setParameter('utilisateur', $utilisateur)
            ->setParameter('statut', StatutSession::COMPLETEE)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : null;
    }
}