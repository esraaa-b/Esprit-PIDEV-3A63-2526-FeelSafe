<?php

namespace App\Entity;

use App\Repository\UrgenceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UrgenceRepository::class)]
#[ORM\Table(name: 'urgence')]
class Urgence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'type_urgence', length: 100, nullable: true)]
    private ?string $typeUrgence = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'niveau_gravite', nullable: true)]
    private ?int $niveauGravite = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $statut = null;

    #[ORM\Column(name: 'date_heure', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateHeure = null;

    #[ORM\Column(name: 'id_utilisateur', nullable: true)]
    private ?int $idUtilisateur = null;

    // Java-compatible status constants
    public const STATUT_EN_ATTENTE = 'en attente';
    public const STATUT_PRISE_EN_CHARGE = 'prise en charge';
    public const STATUT_RESOLUE = 'résolue';

    // Java-compatible gravity levels
    public const GRAVITE_MIN = 1;
    public const GRAVITE_MAX = 5;

    public function __construct()
    {
        $this->dateHeure = new \DateTime();
        $this->statut = self::STATUT_EN_ATTENTE;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTypeUrgence(): ?string
    {
        return $this->typeUrgence;
    }

    public function setTypeUrgence(?string $typeUrgence): static
    {
        $this->typeUrgence = $typeUrgence;
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

    public function getNiveauGravite(): ?int
    {
        return $this->niveauGravite;
    }

    public function setNiveauGravite(?int $niveauGravite): static
    {
        if ($niveauGravite !== null) {
            $niveauGravite = max(self::GRAVITE_MIN, min(self::GRAVITE_MAX, $niveauGravite));
        }
        $this->niveauGravite = $niveauGravite;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
    {
        $allowedStatuts = [self::STATUT_EN_ATTENTE, self::STATUT_PRISE_EN_CHARGE, self::STATUT_RESOLUE];
        if ($statut !== null && !in_array($statut, $allowedStatuts)) {
            throw new \InvalidArgumentException('Invalid status: ' . $statut);
        }
        $this->statut = $statut;
        return $this;
    }

    public function getDateHeure(): ?\DateTimeInterface
    {
        return $this->dateHeure;
    }

    public function setDateHeure(?\DateTimeInterface $dateHeure): static
    {
        $this->dateHeure = $dateHeure;
        return $this;
    }

    public function getIdUtilisateur(): ?int
    {
        return $this->idUtilisateur;
    }

    public function setIdUtilisateur(?int $idUtilisateur): static
    {
        $this->idUtilisateur = $idUtilisateur;
        return $this;
    }

    // Helper methods for Java compatibility
    public function isEnAttente(): bool
    {
        return $this->statut === self::STATUT_EN_ATTENTE;
    }

    public function isPriseEnCharge(): bool
    {
        return $this->statut === self::STATUT_PRISE_EN_CHARGE;
    }

    public function isResolue(): bool
    {
        return $this->statut === self::STATUT_RESOLUE;
    }

    public function isCritique(): bool
    {
        return $this->niveauGravite === 5;
    }
}