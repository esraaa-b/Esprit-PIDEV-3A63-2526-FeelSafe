<?php

namespace App\Service;

use App\Entity\JournalEmotionnel;
use App\Entity\TendanceEmotionnelle;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;

class TendanceGenerator
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public function generateForMonth(Utilisateur $user, int $month, int $year): void
    {
        $repo = $this->em->getRepository(JournalEmotionnel::class);

        // 1️⃣ Calculate date range for the month
        $startDate = new \DateTime("$year-$month-01 00:00:00");
        $endDate = (clone $startDate)->modify('last day of this month')->setTime(23, 59, 59);

        // 2️⃣ Get journals of the month using BETWEEN
        $journals = $repo->createQueryBuilder('j')
            ->where('j.utilisateur = :user')
            ->andWhere('j.dateCreation BETWEEN :start AND :end')
            ->setParameter('user', $user)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->getQuery()
            ->getResult();

        // 3️⃣ Remove old tendances (recalculate clean)
        $this->em->createQueryBuilder()
            ->delete(TendanceEmotionnelle::class, 't')
            ->where('t.utilisateur = :user')
            ->andWhere('t.mois = :month')
            ->andWhere('t.annee = :year')
            ->setParameter('user', $user)
            ->setParameter('month', $month)
            ->setParameter('year', $year)
            ->getQuery()
            ->execute();

        // 4️⃣ If no journals left, we're done (old tendances are deleted)
        if (empty($journals)) {
            return; // Pas de nouvelles tendances à créer
        }

        // 5️⃣ Calculate stats
        $total = count($journals);
        $stats = [];

        foreach ($journals as $journal) {
            $emotion = $journal->getEmotion()->value;
            $stats[$emotion] = ($stats[$emotion] ?? 0) + 1;
        }

        // 6️⃣ Store tendances
        foreach ($stats as $emotion => $count) {
            $trend = new TendanceEmotionnelle();
            $trend->setUtilisateur($user);
            $trend->setMois($month);
            $trend->setAnnee($year);
            $trend->setEmotion($emotion);
            $trend->setTotaleOccurrences($count);
            $trend->setPourcentage((string) round(($count / $total) * 100, 2));
            $trend->setDateCalcul(new \DateTime());

            $this->em->persist($trend);
        }

        $this->em->flush();
    }
}