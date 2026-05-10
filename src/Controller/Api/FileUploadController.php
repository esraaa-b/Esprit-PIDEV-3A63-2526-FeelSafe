<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/upload', name: 'api_upload_')]
class FileUploadController extends AbstractController
{
    #[Route('/image', name: 'image', methods: ['POST'])]
    public function uploadImage(Request $request): JsonResponse
    {
        return $this->handleUpload($request, 'journal_images_dir', 'image');
    }

    #[Route('/audio', name: 'audio', methods: ['POST'])]
    public function uploadAudio(Request $request): JsonResponse
    {
        return $this->handleUpload($request, 'journal_audio_dir', 'audio');
    }

    #[Route('/delete', name: 'delete', methods: ['POST'])]
    public function deleteFile(Request $request): JsonResponse
    {
        $data     = json_decode($request->getContent(), true);
        $filename = $data['filename'] ?? '';
        $type     = $data['type'] ?? 'image';

        if (empty($filename)) {
            return $this->json(['error' => 'No filename'], 400);
        }

        $dir  = $this->getParameter($type === 'audio' ? 'journal_audio_dir' : 'journal_images_dir');
        $path = $dir . '/' . basename($filename);

        if (file_exists($path)) {
            unlink($path);
        }

        return $this->json(['deleted' => true]);
    }

    private function handleUpload(Request $request, string $dirParam, string $fieldName): JsonResponse
    {
        $file = $request->files->get($fieldName);

        if (!$file) {
            return $this->json(['error' => 'No file received'], 400);
        }

        if (!$file->isValid()) {
            return $this->json(['error' => 'Invalid file'], 400);
        }

        $dir      = $this->getParameter($dirParam);
        $origExt  = strtolower($file->guessExtension() ?? $file->getClientOriginalExtension() ?? '');
        $basename = uniqid();

        // Save the original file first
        $origFilename = $basename . '.' . $origExt;
        $file->move($dir, $origFilename);
        $origPath = $dir . '/' . $origFilename;

        // ── Audio: convert WebM/Ogg/Opus → MP3 for JavaFX compatibility ──────
        // JavaFX on Windows cannot play WebM/Opus natively.
        // If FFmpeg is available, convert to MP3 transparently.
        if ($fieldName === 'audio' && in_array($origExt, ['webm', 'ogg', 'opus', 'weba'])) {
            $mp3Filename = $basename . '.mp3';
            $mp3Path     = $dir . '/' . $mp3Filename;

            // Try FFmpeg (must be installed and in PATH, or set full path below)
            $ffmpeg = 'ffmpeg'; // or e.g. 'C:/ffmpeg/bin/ffmpeg.exe' on Windows
            $cmd    = sprintf(
                '%s -i %s -vn -ar 44100 -ac 2 -b:a 128k %s 2>/dev/null',
                escapeshellcmd($ffmpeg),
                escapeshellarg($origPath),
                escapeshellarg($mp3Path)
            );

            exec($cmd, $output, $returnCode);

            if ($returnCode === 0 && file_exists($mp3Path)) {
                // Conversion succeeded — delete the original WebM
                unlink($origPath);
                return $this->json(['filename' => $mp3Filename]);
            }
            // FFmpeg not available or failed — serve the original (won't play in Java)
        }

        return $this->json(['filename' => $origFilename]);
    }
}