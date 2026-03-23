<?php

namespace App\Entity;

use App\Repository\PasswordResetTokenRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: PasswordResetTokenRepository::class)]
#[ORM\Table(name: 'password_reset_token')]
#[ORM\Index(columns: ['token'], name: 'idx_token')]
#[ORM\Index(columns: ['expires_at'], name: 'idx_expires_at')]
class PasswordResetToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Utilisateur $user;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Ignore]
    private string $token;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: 'boolean')]
    private bool $isUsed = false;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->expiresAt = new \DateTimeImmutable('+24 hours');
        $this->isUsed = false;
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): Utilisateur { return $this->user; }
    public function setUser(Utilisateur $user): static { $this->user = $user; return $this; }

    public function getToken(): string { return $this->token; }
    public function setToken(#[\SensitiveParameter] string $token): static { $this->token = $token; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    private function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getExpiresAt(): \DateTimeImmutable { return $this->expiresAt; }
    private function setExpiresAt(\DateTimeImmutable $expiresAt): static { $this->expiresAt = $expiresAt; return $this; }

    public function isUsed(): bool { return $this->isUsed; }
    public function setIsUsed(bool $isUsed): static { $this->isUsed = $isUsed; return $this; }

    public function isValid(): bool
    {
        return !$this->isUsed && $this->expiresAt > new \DateTimeImmutable();
    }
}