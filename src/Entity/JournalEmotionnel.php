<?php

namespace App\Entity;

use App\Repository\JournalEmotionnelRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\EmotionEnum;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: JournalEmotionnelRepository::class)]
#[ORM\HasLifecycleCallbacks]
class JournalEmotionnel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // ✅ Fix 1: enumType already correct, kept as is
    #[ORM\Column(length: 20, enumType: EmotionEnum::class)]
    private EmotionEnum $emotion;

    #[Assert\Length(
        min: 6,
        minMessage: 'Le contenu doit contenir au moins {{ limit }} caractères',
        groups: ['Default']
    )]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $contenu = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $audio = null;

    // ✅ Fix 2: DateTime → DateTimeImmutable, non-nullable, initialized in constructor
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $dateCreation;

    // ✅ Fix 3: non-nullable (removed ?)
    #[ORM\ManyToOne(inversedBy: 'journaux')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Utilisateur $utilisateur;

    // ✅ Fix 4: constructor initializes dateCreation automatically
    public function __construct()
    {
        $this->dateCreation = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmotion(): EmotionEnum
    {
        return $this->emotion;
    }

    public function setEmotion(EmotionEnum $emotion): static
    {
        $this->emotion = $emotion;
        return $this;
    }

    #[Assert\Callback]
    public function validateContenu(ExecutionContextInterface $context, $payload)
    {
        if (!empty($this->contenu) && mb_strlen($this->contenu) < 6) {
            $context->buildViolation('Le contenu doit contenir au moins 6 caractères.')
                ->atPath('contenu')
                ->addViolation();
        }
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(?string $contenu): static
    {
        $this->contenu = $contenu;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getAudio(): ?string
    {
        return $this->audio;
    }

    public function setAudio(?string $audio): static
    {
        $this->audio = $audio;
        return $this;
    }

    public function getDateCreation(): \DateTimeImmutable
    {
        return $this->dateCreation;
    }

    // ✅ Fix 5: setter is now private to prevent manual manipulation
    private function setDateCreation(\DateTimeImmutable $dateCreation): static
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getUtilisateur(): Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;
        return $this;
    }
}