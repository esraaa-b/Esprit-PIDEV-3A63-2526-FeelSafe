<?php

namespace App\Controller;

use App\Entity\JournalEmotionnel;
use App\Enum\EmotionEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dashboard/stars', name: 'app_stars_')]
class StarsController extends AbstractController
{
    #[Route('/data', name: 'data', methods: ['GET'])]
    public function data(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['stars' => 0, 'badge' => false, 'remaining' => 6, 'tooltip' => '']);
        }

        $now         = new \DateTimeImmutable();
        $year        = (int) $now->format('Y');
        $month       = (int) $now->format('n');
        $daysInMonth = (int) $now->format('t');

        // Use a date RANGE — avoids MONTH()/YEAR() DQL functions
        // which can fail with datetime_immutable column type
        $from = new \DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month));
        $to   = new \DateTimeImmutable(sprintf('%04d-%02d-%02d 23:59:59', $year, $month, $daysInMonth));

        $entries = $em->getRepository(JournalEmotionnel::class)
            ->createQueryBuilder('j')
            ->where('j.utilisateur = :user')
            ->andWhere('j.dateCreation >= :from')
            ->andWhere('j.dateCreation <= :to')
            ->setParameter('user', $user)
            ->setParameter('from', $from)
            ->setParameter('to',   $to)
            ->getQuery()
            ->getResult();

        // Build set of day-of-month numbers that have >= 1 positive entry
        $positiveDays = [];
        foreach ($entries as $entry) {
            /** @var JournalEmotionnel $entry */
            if ($entry->getEmotion() === EmotionEnum::TRES_BIEN
                || $entry->getEmotion() === EmotionEnum::BIEN) {
                $positiveDays[(int) $entry->getDateCreation()->format('j')] = true;
            }
        }

        // Java algorithm: every 5 consecutive positive days = 1 star, counter resets
        $stars            = 0;
        $consecutiveCount = 0;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            if (isset($positiveDays[$day])) {
                $consecutiveCount++;
                if ($consecutiveCount === 5) {
                    $stars++;
                    $consecutiveCount = 0;
                }
            } else {
                $consecutiveCount = 0;
            }
        }

        $badge     = $stars >= 6;
        $remaining = max(0, 6 - $stars);

        return $this->json([
            'stars'     => $stars,
            'badge'     => $badge,
            'remaining' => $remaining,
            'tooltip'   => $badge
                ? '🏅 Badge du mois obtenu !'
                : $remaining . ' étoile(s) restante(s) pour le badge 🏅',
        ]);
    }
}
