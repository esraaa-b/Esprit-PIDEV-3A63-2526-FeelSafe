<?php

namespace App\Entity;

use App\Repository\TranslationCacheRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TranslationCacheRepository::class)]
#[ORM\UniqueConstraint(name: 'source_target_idx', columns: ['source_text_hash', 'target_lang'])]
class TranslationCache
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    // ✅ Fix: non-nullable strings
    #[ORM\Column(length: 64)]
    private string $sourceTextHash;

    #[ORM\Column(length: 10)]
    private string $targetLang;

    #[ORM\Column(type: 'text')]
    private string $translatedText;

    // ✅ Fix: non-nullable, initialized in constructor
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getSourceTextHash(): string { return $this->sourceTextHash; }
    public function setSourceTextHash(string $sourceTextHash): static { $this->sourceTextHash = $sourceTextHash; return $this; }

    public function getTargetLang(): string { return $this->targetLang; }
    public function setTargetLang(string $targetLang): static { $this->targetLang = $targetLang; return $this; }

    public function getTranslatedText(): string { return $this->translatedText; }
    public function setTranslatedText(string $translatedText): static { $this->translatedText = $translatedText; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}