<?php

namespace App\Service;

use App\Entity\TendanceEmotionnelle;
use App\Entity\Utilisateur;
use App\Repository\JournalEmotionnelRepository;
use Doctrine\ORM\EntityManagerInterface;

class TendanceGenerator
{
    public function __construct(
        private EntityManagerInterface $em,
        private JournalEmotionnelRepository $journalRepo
    ) {}

    /**
     * @param bool $flush  Set to false when the caller will flush() itself,
     *                     to avoid a second flush and the "Flush in Loop" warning.
     */
    public function generateForMonth(Utilisateur $user, int $month, int $year, bool $flush = true): void
    {
        // 1. Calculate date range for the month
        $startDate = new \DateTimeImmutable("$year-$month-01 00:00:00");
        $endDate   = $startDate->modify('first day of next month');

        // 2. Load journals using JOIN FETCH → single query, no N+1
        $journals = $this->journalRepo->findByUserAndDateRange($user, $startDate, $endDate);

        // 3. Remove old tendances (recalculate clean)
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

        // 4. If no journals, nothing to compute
        if (empty($journals)) {
            return;
        }

        // 5. Calculate stats
        $total = count($journals);
        $stats = [];
        foreach ($journals as $journal) {
            $emotion = $journal->getEmotion()->value;
            $stats[$emotion] = ($stats[$emotion] ?? 0) + 1;
        }

        // 6. Persist all tendances (never inside loop)
        foreach ($stats as $emotion => $count) {
            $trend = new TendanceEmotionnelle();
            $trend->setUtilisateur($user);
            $trend->setMois($month);
            $trend->setAnnee($year);
            $trend->setEmotion($emotion);
            $trend->setTotaleOccurrences($count);
            $trend->setPourcentage((string) round(($count / $total) * 100, 2));
            $this->em->persist($trend);
        }

        // 7. Single flush — skipped when caller handles it to avoid double-flush
        if ($flush) {
            $this->em->flush();
        }
    }
}
