<?php

namespace App\Service;

use App\Entity\RendezVous;
use App\Entity\Utilisateur;
use App\Enum\StatutRendezVous;
use Doctrine\ORM\EntityManagerInterface;

class ForecastService
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function getForecastForProfessional(Utilisateur $pro, int $days = 14, int $lookbackWeeks = 12, float $lambda = 0.35): array
    {
        $today = new \DateTimeImmutable('today');
        $start = $today->modify('-' . $lookbackWeeks . ' weeks');

        $qb = $this->em->createQueryBuilder()
            ->select('r')
            ->from(RendezVous::class, 'r')
            ->where('r.professionnel = :pro')
            ->andWhere('r.dateRdv BETWEEN :start AND :end')
            ->setParameter('pro', $pro)
            ->setParameter('start', new \DateTime($start->format('Y-m-d')))
            ->setParameter('end', new \DateTime($today->format('Y-m-d')))
            ->setMaxResults(1000);

        $rdvs = $qb->getQuery()->getResult();

        $weekdayCounts = array_fill(1, 7, 0.0);
        $baseWeightSum = 0.0;
        for ($k = 0; $k < $lookbackWeeks; $k++) {
            $baseWeightSum += exp(-$lambda * $k);
        }

        foreach ($rdvs as $rdv) {
            if (!$rdv instanceof RendezVous) {
                continue;
            }
            $status = $rdv->getStatut()->value ?? null;
            if ($status === StatutRendezVous::ANNULE->value) {
                continue;
            }
            $date = $rdv->getDateRdv();
            $weekday = (int) $date->format('N'); // 1..7
            $weeksAgo = max(0, (int) floor((($today->getTimestamp() - $date->getTimestamp()) / 86400) / 7));
            $w = exp(-$lambda * $weeksAgo);
            $weekdayCounts[$weekday] += $w;
        }

        $avgByWeekday = [];
        for ($d = 1; $d <= 7; $d++) {
            $avgByWeekday[$d] = $baseWeightSum > 0 ? ($weekdayCounts[$d] / $baseWeightSum) : 0.0;
        }

        $overallPerDay = ($baseWeightSum > 0) ? ((array_sum($weekdayCounts) / $baseWeightSum) / 7.0) : 0.0;

        $forecast = [];
        for ($i = 0; $i < $days; $i++) {
            $d = $today->modify('+' . $i . ' day');
            $wd = (int) $d->format('N');
            $pred = $avgByWeekday[$wd] ?: $overallPerDay;
            $forecast[] = [
                'date' => $d->format('Y-m-d'),
                'predicted' => max(0, round($pred, 2)),
            ];
        }

        return $forecast;
    }

    public function getNextRecommendations(Utilisateur $user, int $limit = 5, int $lookbackMonths = 6, float $lambda = 0.35): array
    {
        $now = new \DateTimeImmutable('now');
        $start = $now->modify('-' . $lookbackMonths . ' months');

        $qb = $this->em->createQueryBuilder()
            ->select('r')
            ->from(RendezVous::class, 'r')
            ->where('r.utilisateur = :user')
            ->andWhere('r.dateRdv BETWEEN :start AND :end')
            ->setParameter('user', $user)
            ->setParameter('start', new \DateTime($start->format('Y-m-d')))
            ->setParameter('end', new \DateTime($now->format('Y-m-d')))
            ->setMaxResults(1000);

        $rdvs = $qb->getQuery()->getResult();

        $slotScores = [];
        foreach ($rdvs as $rdv) {
            $date = $rdv->getDateRdv();
            $time = $rdv->getHeureRdv();
            $weekday = (int) $date->format('N'); // 1..7
            $hour = $time->format('H:i');
            $slotKey = $weekday . '|' . $hour;
            $weeksAgo = max(0, (int) floor((($now->getTimestamp() - $date->getTimestamp()) / 86400) / 7));
            $w = exp(-$lambda * $weeksAgo);
            $delta = 0.0;
            $st = $rdv->getStatut()->value ?? null;
            if ($st === StatutRendezVous::HONORE->value) {
                $delta = 1.0;
            } elseif ($st === StatutRendezVous::ANNULE->value || $st === StatutRendezVous::NON_HONORE->value) {
                $delta = -1.0;
            }
            $slotScores[$slotKey] = ($slotScores[$slotKey] ?? 0.0) + $delta * $w;
        }

        arsort($slotScores);
        $topSlots = array_keys($slotScores);

        $suggestions = [];
        $added = 0;
        $maxDaysScan = 21;
        foreach ($topSlots as $slot) {
            if ($added >= $limit) {
                break;
            }
            [$wd, $hour] = explode('|', $slot);
            $targetWd = (int) $wd;
            $score = round($slotScores[$slot], 2);
            for ($i = 0; $i < $maxDaysScan && $added < $limit; $i++) {
                $d = $now->modify('+' . $i . ' day');
                if ((int) $d->format('N') !== $targetWd) {
                    continue;
                }
                $dateStr = $d->format('Y-m-d');
                $key = $dateStr . ' ' . $hour;
                if (!isset($suggestions[$key]) && $d->format('Y-m-d') >= $now->format('Y-m-d')) {
                    $suggestions[$key] = [
                        'date' => $dateStr,
                        'time' => $hour,
                        'score' => $score,
                    ];
                    $added++;
                }
            }
        }

        if ($added === 0) {
            for ($i = 1; $i <= min($limit, 5); $i++) {
                $d = $now->modify('+' . $i . ' day');
                $suggestions[$d->format('Y-m-d') . ' 10:00'] = [
                    'date' => $d->format('Y-m-d'),
                    'time' => '10:00',
                    'score' => 0.0,
                ];
            }
        }

        return array_values($suggestions);
    }
}
