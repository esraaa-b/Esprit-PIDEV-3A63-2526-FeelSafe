<?php

namespace App\Service;

use App\Entity\Utilisateur;

class ProNotificationService
{
    private string $baseDir;

    public function __construct()
    {
        $this->baseDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'notifications';
        if (!is_dir($this->baseDir)) {
            @mkdir($this->baseDir, 0775, true);
        }
    }

    public function add(Utilisateur $pro, string $type, array $payload): void
    {
        $file = $this->filePath($pro);
        $items = $this->readFile($file);
        $items[] = [
            'id' => uniqid(),
            'type' => $type,
            'payload' => $payload,
            'createdAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
        $this->writeFile($file, $items);
    }

    public function list(Utilisateur $pro): array
    {
        $file = $this->filePath($pro);
        $items = $this->readFile($file);
        usort($items, fn ($a, $b) => strcmp($b['createdAt'] ?? '', $a['createdAt'] ?? ''));
        return array_slice($items, 0, 50);
    }

    public function clear(Utilisateur $pro): bool
    {
        $file = $this->filePath($pro);
        if (is_file($file)) {
            try {
                if (@unlink($file)) {
                    return true;
                }
            } catch (\Throwable $e) {
                // ignore and fallback
            }
        }
        return $this->writeFile($file, []);
    }

    private function filePath(Utilisateur $pro): string
    {
        $id = $pro->getId() ?? 0;
        return $this->baseDir . DIRECTORY_SEPARATOR . ('pro_' . $id . '.json');
    }

    private function readFile(string $file): array
    {
        if (!is_file($file)) {
            return [];
        }
        try {
            $json = file_get_contents($file);
            $data = json_decode($json, true);
            return is_array($data) ? $data : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function writeFile(string $file, array $items): bool
    {
        try {
            $bytes = file_put_contents($file, json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return $bytes !== false;
        } catch (\Throwable $e) {
            // ignore
            return false;
        }
    }
}
