<?php

namespace App\Controller\Admin;

use App\Entity\JournalEmotionnel;
use App\Enum\EmotionEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Nucleos\DompdfBundle\Factory\DompdfFactoryInterface;
use App\Entity\Utilisateur;

#[Route('/admin/journal')]
class JournalExportController extends AbstractController
{
    /**
     * Returns calendar heatmap data: one entry per day for the last 365 days.
     * Response: [{ date: 'Y-m-d', count: int }, ...]
     */
    #[Route('/calendar-data', name: 'app_journal_calendar_data', methods: ['GET'])]
    public function calendarData(EntityManagerInterface $em): JsonResponse
    {
        /** @var \App\Entity\Utilisateur $user */
        $user = $this->getUser();

        $oneYearAgo = new \DateTime('-365 days');
        $oneYearAgo->setTime(0, 0, 0);

        $journals = $em->getRepository(JournalEmotionnel::class)
            ->createQueryBuilder('j')
            ->where('j.utilisateur = :user')
            ->andWhere('j.dateCreation >= :start')
            ->setParameter('user', $user)
            ->setParameter('start', $oneYearAgo)
            ->orderBy('j.dateCreation', 'ASC')
            ->getQuery()
            ->getResult();

        // Group counts by date
        $countsByDate = [];
        foreach ($journals as $journal) {
            $dateStr = $journal->getDateCreation()->format('Y-m-d');
            $countsByDate[$dateStr] = ($countsByDate[$dateStr] ?? 0) + 1;
        }

        // Build array for all days in range
        $result = [];
        $current = clone $oneYearAgo;
        $today = new \DateTime();
        $today->setTime(23, 59, 59);

        while ($current <= $today) {
            $dateStr = $current->format('Y-m-d');
            $result[] = [
                'date'  => $dateStr,
                'count' => $countsByDate[$dateStr] ?? 0,
            ];
            $current->modify('+1 day');
        }

        return $this->json($result);
    }

    /**
     * Exports a PDF report of the user's emotional journal statistics.
     */
#[Route('/export-pdf/{userId}', name: 'app_admin_journal_export_pdf', methods: ['GET'])]
    public function exportPdf(int $userId, EntityManagerInterface $em, DompdfFactoryInterface $dompdfFactory): Response
    {
        /** @var \App\Entity\Utilisateur $user */
        // ── Fetch the target user, not the logged-in admin ──────────────────
    $user = $em->getRepository(\App\Entity\Utilisateur::class)->find($userId);

    if (!$user) {
        throw $this->createNotFoundException('Utilisateur introuvable.');
    }

    $journals = $em->getRepository(JournalEmotionnel::class)
        ->findBy(['utilisateur' => $user], ['dateCreation' => 'DESC']);


        $totalEntries   = count($journals);
        $moodCounts     = [];
        $entriesByMonth = [];

        foreach (EmotionEnum::cases() as $e) {
            $moodCounts[$e->value] = ['label' => $e->label(), 'icon' => $e->icon(), 'count' => 0];
        }

        foreach ($journals as $journal) {
            $moodCounts[$journal->getEmotion()->value]['count']++;
            $monthKey = $journal->getDateCreation()->format('Y-m');
            $entriesByMonth[$monthKey] = ($entriesByMonth[$monthKey] ?? 0) + 1;
        }

        $positiveCount   = ($moodCounts['tres_bien']['count'] ?? 0) + ($moodCounts['bien']['count'] ?? 0);
        $positivePercent = $totalEntries > 0 ? round(($positiveCount / $totalEntries) * 100) : 0;

        $thirtyDaysAgo  = new \DateTime('-30 days');
        $monthEntries   = count(array_filter($journals, fn($j) => $j->getDateCreation() >= $thirtyDaysAgo));

        $dates = array_unique(array_map(fn($j) => $j->getDateCreation()->format('Y-m-d'), $journals));
        rsort($dates);
        $streak = 0;
        if (!empty($dates)) {
            $streak = 1;
            for ($i = 0; $i < count($dates) - 1; $i++) {
                $diff = (int)(new \DateTime($dates[$i]))->diff(new \DateTime($dates[$i + 1]))->days;
                if ($diff === 1) { $streak++; } else { break; }
            }
        }

        $recentTen = array_slice($journals, 0, 10);
        ksort($entriesByMonth);
        $lastSixMonths = array_slice($entriesByMonth, -6, 6, true);

        $emotionColors = [
            'tres_bien' => '#16a34a',
            'bien'      => '#4ade80',
            'neutre'    => '#eab308',
            'pas_bien'  => '#fb923c',
            'tres_mal'  => '#dc2626',
        ];

        $html = $this->renderView('admin/journal/pdf_export.html.twig', [
            'user'            => $user,
            'generatedAt'     => new \DateTime(),
            'totalEntries'    => $totalEntries,
            'monthEntries'    => $monthEntries,
            'consecutiveDays' => $streak,
            'positivePercent' => $positivePercent,
            'moodCounts'      => $moodCounts,
            'emotionColors'   => $emotionColors,
            'recentTen'       => $recentTen,
            'entriesByMonth'  => $lastSixMonths,
        ]);

        // ── Generate PDF via NucleosDompdfBundle ────────────────────────────
        $dompdf = $dompdfFactory->create();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'journal-emotionnel-' . (new \DateTime())->format('Y-m-d') . '.pdf';

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }
}