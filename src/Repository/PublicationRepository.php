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

    //    /**
    //     * @return Publication[] Returns an array of Publication objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Publication
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    public function findUnreadNotifications(int $userId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :userId')
            ->andWhere('p.notificationRead = :read')
            ->andWhere('p.notificationMessage IS NOT NULL')
            ->setParameter('userId', $userId)
            ->setParameter('read', false)
            ->orderBy('p.notificationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
    public function findByTopic(string $topic): array
    {
        $clusters = [
            'Anxiété' => ['anxiété', 'anxieux', 'peur', 'panique', 'crise', 'inquiétude', 'anxiety', 'anxious', 'fear', 'panic', 'attack', 'worry'],
            'Dépression' => ['dépression', 'déprimé', 'triste', 'tristesse', 'vide', 'désespoir', 'depression', 'depressed', 'sad', 'sadness', 'empty', 'hopeless', 'despair'],
            'Stress' => ['stress', 'stressé', 'pression', 'surmenage', 'épuisement', 'stressed', 'pressure', 'burnout', 'exhausted'],
            'Angoisse' => ['angoisse', 'angoisse', 'oppression', 'thoracique', 'tremblement', 'anguish', 'distress', 'tight chest', 'shaking'],
            'Addiction' => ['addiction', 'dépendance', 'drogue', 'alcool', 'tabac', 'jeu', 'drug', 'alcohol', 'smoking', 'gambling', 'substance'],
            'Solitude' => ['solitude', 'seul', 'isolement', 'isolé', 'délaissé', 'loneliness', 'lonely', 'alone', 'isolation', 'isolated', 'neglected'],
        ];

        $keywords = $clusters[$topic] ?? [$topic];
        
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.pubCom', 'c'); // Pour chercher aussi dans les commentaires

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
            ->getQuery()
            ->getResult();
    }
}
