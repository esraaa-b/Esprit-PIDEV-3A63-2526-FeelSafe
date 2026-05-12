<?php

namespace App\Controller;

use App\Entity\JournalEmotionnel;
use App\Enum\EmotionEnum;
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\Client\BaseDashboardController;
use Nucleos\DompdfBundle\Factory\DompdfFactoryInterface;

#[Route('/dashboard/journal')]
class JournalController extends BaseDashboardController
{
    private function cloudinary(): UploadApi
    {
        Configuration::instance($_ENV['CLOUDINARY_URL']);
        return new UploadApi();
    }

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
                'journals'     => $journals,
                'moodOptions'  => EmotionEnum::cases(),
                'stats'        => $this->computeStats($journals),
                'moodStats'    => $this->computeMoodStats($journals),
                'calendarData' => $this->computeCalendarData($journals),
            ]
        ));
    }

    #[Route('/new', name: 'app_journal_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('create_journal', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_journal');
        }

        $journal = new JournalEmotionnel();
        $journal->setUtilisateur($this->getUser());
        $journal->setEmotion(EmotionEnum::from($request->request->get('emotion')));
        $journal->setContenu($request->request->get('contenu'));

        $imageFile = $request->files->get('image');
        if ($imageFile) {
            try {
                $result = $this->cloudinary()->upload(
                    $imageFile->getPathname(),
                    ['folder' => 'feelsafe/images']
                );
                $journal->setImage($result['public_id']);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur upload image : ' . $e->getMessage());
            }
        }

        $audioFile = $request->files->get('audio');
        if ($audioFile) {
            try {
                $result = $this->cloudinary()->upload(
                    $audioFile->getPathname(),
                    ['resource_type' => 'raw', 'folder' => 'feelsafe/audio']
                );
                $journal->setAudio($result['public_id']);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur upload audio : ' . $e->getMessage());
            }
        }

        $em->persist($journal);
        $em->flush();

        $this->addFlash('success', 'Entrée ajoutée avec succès');
        return $this->redirectToRoute('app_journal');
    }

    #[Route('/{id}/edit', name: 'app_journal_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $journal = $em->getRepository(JournalEmotionnel::class)->find($id);

        if (!$journal) {
            $this->addFlash('error', 'Entrée introuvable.');
            return $this->redirectToRoute('app_journal');
        }

        if ($journal->getUtilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('edit_journal_' . $id, $request->request->get('_token'))) {
                $this->addFlash('error', 'Token CSRF invalide.');
                return $this->redirectToRoute('app_journal');
            }

            $journal->setEmotion(EmotionEnum::from($request->request->get('emotion')));
            $journal->setContenu($request->request->get('contenu'));

            // ── Image ────────────────────────────────────────────────────────
            $removeImage = $request->request->get('remove_image') === '1';
            if ($removeImage && $journal->getImage()) {
                try {
                    $this->cloudinary()->destroy($journal->getImage());
                } catch (\Exception $e) {
                    // log but don't block
                    error_log('[Cloudinary] Delete image failed: ' . $e->getMessage());
                }
                $journal->setImage(null);
            }

            $imageFile = $request->files->get('image');
            if ($imageFile) {
                // Delete old image first
                if ($journal->getImage()) {
                    try {
                        $this->cloudinary()->destroy($journal->getImage());
                    } catch (\Exception $e) {
                        error_log('[Cloudinary] Delete old image failed: ' . $e->getMessage());
                    }
                }
                try {
                    $result = $this->cloudinary()->upload(
                        $imageFile->getPathname(),
                        ['folder' => 'feelsafe/images']
                    );
                    $journal->setImage($result['public_id']);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur upload image : ' . $e->getMessage());
                }
            }

            // ── Audio ────────────────────────────────────────────────────────
            $removeAudio = $request->request->get('remove_audio') === '1';
            if ($removeAudio && $journal->getAudio()) {
                try {
                    $this->cloudinary()->destroy($journal->getAudio(), ['resource_type' => 'raw']);
                } catch (\Exception $e) {
                    error_log('[Cloudinary] Delete audio failed: ' . $e->getMessage());
                }
                $journal->setAudio(null);
            }

            $audioFile = $request->files->get('audio');
            if ($audioFile) {
                // Delete old audio first
                if ($journal->getAudio()) {
                    try {
                        $this->cloudinary()->destroy($journal->getAudio(), ['resource_type' => 'raw']);
                    } catch (\Exception $e) {
                        error_log('[Cloudinary] Delete old audio failed: ' . $e->getMessage());
                    }
                }
                try {
                    $result = $this->cloudinary()->upload(
                        $audioFile->getPathname(),
                        ['resource_type' => 'raw', 'folder' => 'feelsafe/audio']
                    );
                    $journal->setAudio($result['public_id']);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur upload audio : ' . $e->getMessage());
                }
            }

            $em->flush();

            $this->addFlash('success', 'Journal modifié avec succès');
            return $this->redirectToRoute('app_journal');
        }

        return $this->render('dashboard/journal/edit.html.twig', array_merge(
            $this->getUserData(),
            [
                'journal'     => $journal,
                'moodOptions' => EmotionEnum::cases(),
            ]
        ));
    }

    #[Route('/{id}/delete', name: 'app_journal_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $journal = $em->getRepository(JournalEmotionnel::class)->find($id);

        if (!$journal) {
            $this->addFlash('error', 'Entrée introuvable.');
            return $this->redirectToRoute('app_journal');
        }

        if ($journal->getUtilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('delete' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_journal');
        }

        if ($journal->getImage()) {
            try {
                $this->cloudinary()->destroy($journal->getImage());
            } catch (\Exception $e) {
                error_log('[Cloudinary] Delete image failed: ' . $e->getMessage());
            }
        }

        if ($journal->getAudio()) {
            try {
                $this->cloudinary()->destroy($journal->getAudio(), ['resource_type' => 'raw']);
            } catch (\Exception $e) {
                error_log('[Cloudinary] Delete audio failed: ' . $e->getMessage());
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
        $journals = $em->getRepository(JournalEmotionnel::class)
            ->findBy(['utilisateur' => $this->getUser()], ['dateCreation' => 'DESC']);

        return new Response('PDF export', 200, ['Content-Type' => 'application/pdf']);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function computeStats(array $journals): array
    {
        $now   = new \DateTimeImmutable();
        $month = (int) $now->format('n');
        $year  = (int) $now->format('Y');

        $monthEntries = array_filter($journals, fn($j) =>
            (int) $j->getDateCreation()->format('n') === $month &&
            (int) $j->getDateCreation()->format('Y') === $year
        );

        $days = array_unique(array_map(
            fn($j) => $j->getDateCreation()->format('Y-m-d'),
            $journals
        ));
        rsort($days);
        $streak = 0;
        if (!empty($days)) {
            $streak = 1;
            for ($i = 0; $i < count($days) - 1; $i++) {
                $diff = (new \DateTime($days[$i]))->diff(new \DateTime($days[$i + 1]))->days;
                if ($diff === 1) $streak++; else break;
            }
        }

        $positive = array_filter($journals, fn($j) =>
            $j->getEmotion() === EmotionEnum::TRES_BIEN || $j->getEmotion() === EmotionEnum::BIEN
        );
        $posPercent = count($journals) > 0
            ? (int) round(count($positive) * 100 / count($journals))
            : 0;

        return [
            'monthEntries'       => count($monthEntries),
            'consecutiveDays'    => $streak,
            'positivePercentage' => $posPercent,
        ];
    }

    private function computeMoodStats(array $journals): array
    {
        $stats = [];
        foreach (EmotionEnum::cases() as $emotion) {
            $stats[$emotion->value] = count(array_filter(
                $journals, fn($j) => $j->getEmotion() === $emotion
            ));
        }
        return $stats;
    }

    private function computeCalendarData(array $journals): array
    {
        $byDay = [];
        foreach ($journals as $j) {
            $date = $j->getDateCreation()->format('Y-m-d');
            if (!isset($byDay[$date])) {
                $byDay[$date] = ['emotions' => []];
            }
            $val = $j->getEmotion()->value;
            $byDay[$date]['emotions'][$val] = ($byDay[$date]['emotions'][$val] ?? 0) + 1;
        }

        $emotionLabels = [
            'tres_bien' => 'Très bien',
            'bien'      => 'Bien',
            'neutre'    => 'Neutre',
            'pas_bien'  => 'Pas bien',
            'tres_mal'  => 'Très mal',
        ];

        $result = [];
        foreach ($byDay as $date => $data) {
            arsort($data['emotions']);
            $dominantEmotion = array_key_first($data['emotions']);
            $count = array_sum($data['emotions']);

            $result[] = [
                'date'            => $date,
                'count'           => $count,
                'dominantEmotion' => $dominantEmotion,
                'dominantLabel'   => $emotionLabels[$dominantEmotion] ?? $dominantEmotion,
            ];
        }

        return $result;
    }
}