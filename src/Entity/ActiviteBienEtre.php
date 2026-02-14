<?php

namespace App\Entity;

use App\Repository\ActiviteBienEtreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\NiveauDifficulte;

#[ORM\Entity(repositoryClass: ActiviteBienEtreRepository::class)]
class ActiviteBienEtre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    private ?string $nomActivite = null;

    #[ORM\Column(length: 100)]
    private ?string $typeActivite = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?int $dureeSuggeree = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $categorie = null;

    #[ORM\Column(length: 20, enumType: NiveauDifficulte::class, nullable: true)]
    private ?NiveauDifficulte $niveauDifficulte = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $objectifEmotionnel = null;

    #[ORM\Column(nullable: true)]
    private ?bool $estPredefinie = true;

    #[ORM\Column(nullable: true)]
    private ?bool $estActive = true;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $instructionsDetaillees = null;

    #[ORM\Column]
    private ?\DateTime $dateCreation = null;

    #[ORM\ManyToOne(inversedBy: 'activitesCrees')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Utilisateur $creePar = null;

    /**
     * @var Collection<int, SessionActivite>
     */
    #[ORM\OneToMany(targetEntity: SessionActivite::class, mappedBy: 'activite')]
    private Collection $sessions;

    public function __construct()
    {
        $this->sessions = new ArrayCollection();
        $this->dateCreation = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomActivite(): ?string
    {
        return $this->nomActivite;
    }

    public function setNomActivite(string $nomActivite): static
    {
        $this->nomActivite = $nomActivite;
        return $this;
    }

    public function getTypeActivite(): ?string
    {
        return $this->typeActivite;
    }

    public function setTypeActivite(string $typeActivite): static
    {
        $this->typeActivite = $typeActivite;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDureeSuggeree(): ?int
    {
        return $this->dureeSuggeree;
    }

    public function setDureeSuggeree(?int $dureeSuggeree): static
    {
        $this->dureeSuggeree = $dureeSuggeree;
        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(?string $categorie): static
    {
        $this->categorie = $categorie;
        return $this;
    }

    public function getNiveauDifficulte(): ?NiveauDifficulte
    {
        return $this->niveauDifficulte;
    }

    public function setNiveauDifficulte(?NiveauDifficulte $niveauDifficulte): static
    {
        $this->niveauDifficulte = $niveauDifficulte;
        return $this;
    }

    public function getObjectifEmotionnel(): ?string
    {
        return $this->objectifEmotionnel;
    }

    public function setObjectifEmotionnel(?string $objectifEmotionnel): static
    {
        $this->objectifEmotionnel = $objectifEmotionnel;
        return $this;
    }

    public function isEstPredefinie(): ?bool
    {
        return $this->estPredefinie;
    }

    public function setEstPredefinie(?bool $estPredefinie): static
    {
        $this->estPredefinie = $estPredefinie;
        return $this;
    }

    public function isEstActive(): ?bool
    {
        return $this->estActive;
    }

    public function setEstActive(?bool $estActive): static
    {
        $this->estActive = $estActive;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function getInstructionsDetaillees(): ?string
    {
        return $this->instructionsDetaillees;
    }

    public function setInstructionsDetaillees(?string $instructionsDetaillees): static
    {
        $this->instructionsDetaillees = $instructionsDetaillees;
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

    public function getCreePar(): ?Utilisateur
    {
        return $this->creePar;
    }

    public function setCreePar(?Utilisateur $creePar): static
    {
        $this->creePar = $creePar;
        return $this;
    }

    /**
     * @return Collection<int, SessionActivite>
     */
    public function getSessions(): Collection
    {
        return $this->sessions;
    }

    public function addSession(SessionActivite $session): static
    {
        if (!$this->sessions->contains($session)) {
            $this->sessions->add($session);
            $session->setActivite($this);
        }
        return $this;
    }

    public function removeSession(SessionActivite $session): static
    {
        if ($this->sessions->removeElement($session)) {
            if ($session->getActivite() === $this) {
                $session->setActivite(null);
            }
        }
        return $this;
    }
}