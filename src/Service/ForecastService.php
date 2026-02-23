<?php

namespace App\Service;

use App\Entity\RendezVous;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;

class ForecastService
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function forecastForProfessional(Utilisateur $pro, int $days = 14, ?\DateTimeInterface $fromDate = null): array
    {
        $today = $fromDate ? new \DateTime($fromDate->format('Y-m-d')) : new \DateTime('today');
        $start = (clone $today)->modify('-90 days');
        $qb = $this->em->createQuery(
            'SELECT r.dateRdv AS d, COUNT(r.id) AS c
             FROM App\Entity\RendezVous r
             WHERE r.professionnel = :pro AND r.dateRdv BETWEEN :start AND :end
             GROUP BY r.dateRdv
             ORDER BY r.dateRdv ASC'
        )->setParameter('pro', $pro)
         ->setParameter('start', $start)
         ->setParameter('end', $today);

        $rows = $qb->getResult();
        $byDow = [];
        $byDate = [];
        foreach ($rows as $row) {
            $date = $row['d'];
            if (!$date instanceof \DateTime) {
                $date = new \DateTime($row['d']);
            }
            $key = $date->format('Y-m-d');
            $count = (int) $row['c'];
            $byDate[$key] = $count;
            $dow = (int) $date->format('w');
            $byDow[$dow] = $byDow[$dow] ?? [];
            $byDow[$dow][$key] = $count;
        }

        $predictions = [];
        $lambda = 0.3;
        for ($i = 1; $i <= $days; $i++) {
            $d = (clone $today)->modify("+$i day");
            $dow = (int) $d->format('w');
            $weights = [];
            $values = [];
            if (!empty($byDow[$dow])) {
                foreach ($byDow[$dow] as $dateStr => $c) {
                    $past = new \DateTime($dateStr);
                    $diffDays = (int) $past->diff($today)->format('%a');
                    $weeksAgo = $diffDays / 7.0;
                    $w = exp(-$lambda * $weeksAgo);
                    $weights[] = $w;
                    $values[] = $c;
                }
            }
            $pred = 0.0;
            if (!empty($weights)) {
                $sumW = array_sum($weights);
                $sum = 0.0;
                for ($k = 0; $k < count($weights); $k++) {
                    $sum += $weights[$k] * $values[$k];
                }
                $pred = $sumW > 0 ? $sum / $sumW : 0.0;
            } else {
                $pred = !empty($byDate) ? array_sum($byDate) / max(count($byDate), 1) : 0.0;
            }
            $predictions[] = [
                'date' => $d->format('Y-m-d'),
                'weekday' => $d->format('l'),
                'predicted' => max(0, (int) round($pred)),
            ];
        }

        return $predictions;
    }
    public function getNextRecommendations(Utilisateur $client, int $limit = 6): array
{
    // Get past appointments for this client
    $rdvs = $this->em->getRepository(RendezVous::class)->findBy(
        ['utilisateur' => $client],
        ['dateRdv' => 'DESC']
    );

    if (empty($rdvs)) {
        return [];
    }

    // Analyze preferred days of week and hours
    $dowCounts = [];
    $hourCounts = [];
    $modeCounts = [];

    foreach ($rdvs as $rdv) {
        $dow = $rdv->getDateRdv()->format('w'); // 0=Sun, 6=Sat
        $hour = $rdv->getHeureRdv()->format('H');
        $mode = $rdv->getMode()->value;

        $dowCounts[$dow] = ($dowCounts[$dow] ?? 0) + 1;
        $hourCounts[$hour] = ($hourCounts[$hour] ?? 0) + 1;
        $modeCounts[$mode] = ($modeCounts[$mode] ?? 0) + 1;
    }

    // Sort to get most frequent preferences
    arsort($dowCounts);
    arsort($hourCounts);
    arsort($modeCounts);

    $preferredDows = array_keys($dowCounts);
    $preferredHours = array_keys($hourCounts);
    $preferredMode = array_key_first($modeCounts);

    // Generate future slot suggestions starting from tomorrow
    $suggestions = [];
    $today = new \DateTime('today');
    $maxDaysAhead = 60;

    for ($i = 1; $i <= $maxDaysAhead && count($suggestions) < $limit; $i++) {
        $candidate = (clone $today)->modify("+$i day");
        $dow = $candidate->format('w');

        // Only suggest days matching preferred days of week
        if (!in_array($dow, $preferredDows, true)) {
            continue;
        }

        foreach ($preferredHours as $hour) {
            if (count($suggestions) >= $limit) {
                break;
            }
            $suggestions[] = [
                'date'    => $candidate->format('Y-m-d'),
                'weekday' => $candidate->format('l'),
                'hour'    => $hour . ':00',
                'mode'    => $preferredMode,
            ];
            break; // one suggestion per day
        }
    }

    return $suggestions;
}
}

