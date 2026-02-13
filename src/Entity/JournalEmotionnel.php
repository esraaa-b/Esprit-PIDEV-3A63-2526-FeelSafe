<?php

namespace App\Entity;

use App\Repository\JournalEmotionnelRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\EmotionEnum;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: JournalEmotionnelRepository::class)]
class JournalEmotionnel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, enumType: EmotionEnum::class)]
    private EmotionEnum $emotion;
    #[Assert\Length(
    min: 6,
    minMessage: 'Le contenu doit contenir au moins {{ limit }} caractères',
    groups: ['Default'] // S'applique à tout le monde
)]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $contenu = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $audio = null;

    #[ORM\Column]
    private ?\DateTime $dateCreation = null;

    #[ORM\ManyToOne(inversedBy: 'userjour')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $utilisateur = null;

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
    // Si le contenu n'est PAS vide, on vérifie la longueur
    if (!empty($this->contenu) && mb_strlen($this->contenu) < 6) {
        $context->buildViolation('Le contenu doit contenir au moins 6 caractères.')
            ->atPath('contenu')
            ->addViolation();
    }
    
    // ✅ Pas de vérification si le contenu est vide
    // Car on peut avoir une image ou un audio sans texte
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

    public function getDateCreation(): ?\DateTime
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTime $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }
}
