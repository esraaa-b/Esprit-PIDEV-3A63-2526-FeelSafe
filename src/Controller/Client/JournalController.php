<?php

namespace App\Controller\Client;

use App\Entity\JournalEmotionnel;
use App\Enum\EmotionEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\Client\BaseDashboardController;
use Nucleos\DompdfBundle\Factory\DompdfFactoryInterface;

#[Route('/dashboard/journal')]
class JournalController extends BaseDashboardController
{
    #[Route('', name: 'app_journal')]
    public function index(EntityManagerInterface $em): Response
    {
        $journals = $em->getRepository(JournalEmotionnel::class)
            ->findBy(
                ['utilisateur' => $this->getUser()],
                ['dateCreation' => 'DESC']
            );

        return $this->render('client/journal/index.html.twig', array_merge(
            $this->getUserData(),
            [
                'journals' => $journals,
                'moodOptions' => EmotionEnum::cases(),
            ]
        ));
    }

    #[Route('/new', name: 'app_journal_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $journal = new JournalEmotionnel();
        $journal->setEmotion(EmotionEnum::from($request->request->get('emotion')));
        $journal->setContenu($request->request->get('contenu'));
        $journal->setDateCreation(new \DateTime());
        $journal->setUtilisateur($this->getUser());

        // Gestion de l'image
        $imageFile = $request->files->get('image');
        if ($imageFile) {
            $newFilename = uniqid() . '.' . $imageFile->guessExtension();
            $imageFile->move(
                $this->getParameter('journals_images_directory'),
                $newFilename
            );
            $journal->setImage($newFilename);
        }

        // Gestion de l'audio
        $audioFile = $request->files->get('audio');
        if ($audioFile) {
            $newFilename = uniqid() . '.' . $audioFile->guessExtension();
            $audioFile->move(
                $this->getParameter('journals_audio_directory'),
                $newFilename
            );
            $journal->setAudio($newFilename);
        }

        $em->persist($journal);
        $em->flush();

        $this->addFlash('success', 'Journal créé avec succès');
        return $this->redirectToRoute('app_journal');
    }

    #[Route('/{id}/edit', name: 'app_journal_edit')]
    public function edit(
        JournalEmotionnel $journal,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('OWNER', $journal);

        if ($request->isMethod('POST')) {
            $journal->setEmotion(EmotionEnum::from($request->request->get('emotion')));
            $journal->setContenu($request->request->get('contenu'));

            // Gestion de l'image
            $imageFile = $request->files->get('image');
            if ($imageFile) {
                // Supprimer l'ancienne image
                if ($journal->getImage()) {
                    $oldPath = $this->getParameter('journals_images_directory') . '/' . $journal->getImage();
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }
                
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move(
                    $this->getParameter('journals_images_directory'),
                    $newFilename
                );
                $journal->setImage($newFilename);
            }

            // Gestion de l'audio
            $audioFile = $request->files->get('audio');
            if ($audioFile) {
                // Supprimer l'ancien audio
                if ($journal->getAudio()) {
                    $oldPath = $this->getParameter('journals_audio_directory') . '/' . $journal->getAudio();
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }
                
                $newFilename = uniqid() . '.' . $audioFile->guessExtension();
                $audioFile->move(
                    $this->getParameter('journals_audio_directory'),
                    $newFilename
                );
                $journal->setAudio($newFilename);
            }

            $em->flush();

            $this->addFlash('success', 'Journal modifié avec succès');
            return $this->redirectToRoute('app_journal');
        }

        return $this->render('dashboard/journal/edit.html.twig', array_merge(
            $this->getUserData(),
            [
                'journal' => $journal,
                'moodOptions' => EmotionEnum::cases(),
            ]
        ));
    }

    #[Route('/{id}/delete', name: 'app_journal_delete', methods: ['POST'])]
    public function delete(JournalEmotionnel $journal, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('OWNER', $journal);

        // Supprimer les fichiers
        if ($journal->getImage()) {
            $imagePath = $this->getParameter('journals_images_directory') . '/' . $journal->getImage();
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        if ($journal->getAudio()) {
            $audioPath = $this->getParameter('journals_audio_directory') . '/' . $journal->getAudio();
            if (file_exists($audioPath)) {
                unlink($audioPath);
            }
        }

        $em->remove($journal);
        $em->flush();

        $this->addFlash('success', 'Journal supprimé avec succès');
        return $this->redirectToRoute('app_journal');
    }

    #[Route('/export-pdf', name: 'app_journal_export_pdf', methods: ['GET'])]
    public function exportPdf(EntityManagerInterface $em, DompdfFactoryInterface $dompdfFactory): Response
    {
        /** @var \App\Entity\Utilisateur $user */
        $user = $this->getUser();

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