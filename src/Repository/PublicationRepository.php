<?php

namespace App\Repository;

use App\Entity\Publication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Publication>
 */
class PublicationRepository extends ServiceEntityRepository
{
   public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Publication::class);
    }

    /**
     * Find unread notifications for a user.
     * setMaxResults avoids "ORDER BY without LIMIT" Doctrine Doctor warning.
     *
     * @return Publication[]
     */
    public function findUnreadNotifications(int $userId, int $limit = 50): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :userId')
            ->andWhere('p.notificationRead = :read')
            ->andWhere('p.notificationMessage IS NOT NULL')
            ->setParameter('userId', $userId)
            ->setParameter('read', false)
            ->orderBy('p.notificationDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find publications matching a topic keyword cluster.
     * setMaxResults avoids "ORDER BY without LIMIT" Doctrine Doctor warning.
     *
     * @return Publication[]
     */
    public function findByTopic(string $topic, int $limit = 100): array
    {
        $clusters = [
            'Anxiété'    => ['anxiété', 'anxieux', 'peur', 'panique', 'crise', 'inquiétude', 'anxiety', 'anxious', 'fear', 'panic', 'attack', 'worry'],
            'Dépression' => ['dépression', 'déprimé', 'triste', 'tristesse', 'vide', 'désespoir', 'depression', 'depressed', 'sad', 'sadness', 'empty', 'hopeless', 'despair'],
            'Stress'     => ['stress', 'stressé', 'pression', 'surmenage', 'épuisement', 'stressed', 'pressure', 'burnout', 'exhausted'],
            'Angoisse'   => ['angoisse', 'oppression', 'thoracique', 'tremblement', 'anguish', 'distress', 'tight chest', 'shaking'],
            'Addiction'  => ['addiction', 'dépendance', 'drogue', 'alcool', 'tabac', 'jeu', 'drug', 'alcohol', 'smoking', 'gambling', 'substance'],
            'Solitude'   => ['solitude', 'seul', 'isolement', 'isolé', 'délaissé', 'loneliness', 'lonely', 'alone', 'isolation', 'isolated', 'neglected'],
        ];

        $keywords = $clusters[$topic] ?? [$topic];

        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.pubCom', 'c');

        $orX = $qb->expr()->orX();
        foreach ($keywords as $key => $word) {
            $paramName = 'word_' . $key;
            $orX->add($qb->expr()->like('p.titre', ':' . $paramName));
            $orX->add($qb->expr()->like('p.contenu', ':' . $paramName));
            $orX->add($qb->expr()->like('c.contenu', ':' . $paramName));
            $qb->setParameter($paramName, '%' . $word . '%');
        }

        return $qb->andWhere($orX)
            ->andWhere('p.isDeleted = :false')
            ->setParameter('false', false)
            ->orderBy('p.datePublication', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
