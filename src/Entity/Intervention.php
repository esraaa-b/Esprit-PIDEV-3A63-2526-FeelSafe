<?php

namespace App\Entity;

use App\Repository\InterventionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InterventionRepository::class)]
#[ORM\Table(name: 'intervention')]
class Intervention
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'id_urgence', nullable: true)]
    private ?int $idUrgence = null;

    #[ORM\Column(name: 'type_intervention', length: 50, nullable: true)]
    private ?string $typeIntervention = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $statut = null;

    #[ORM\Column(name: 'date_heure', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateHeure = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(name: 'id_admin', nullable: true)]
    private ?int $idAdmin = null;

    // Java-compatible type constants
    public const TYPE_APPEL = 'appel';
    public const TYPE_EQUIPE = 'équipe';
    public const TYPE_ESCALADE = 'escalade';

    // Java-compatible status constants
    public const STATUT_EN_COURS = 'en cours';
    public const STATUT_TERMINEE = 'terminée';
    public const STATUT_ANNULEE = 'annulée';

    public function __construct()
    {
        $this->dateHeure = new \DateTime();
        $this->statut = self::STATUT_EN_COURS;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdUrgence(): ?int
    {
        return $this->idUrgence;
    }

    public function setIdUrgence(?int $idUrgence): static
    {
        $this->idUrgence = $idUrgence;
        return $this;
    }

    public function getTypeIntervention(): ?string
    {
        return $this->typeIntervention;
    }

    public function setTypeIntervention(?string $typeIntervention): static
    {
        $allowedTypes = [self::TYPE_APPEL, self::TYPE_EQUIPE, self::TYPE_ESCALADE];
        if ($typeIntervention !== null && !in_array($typeIntervention, $allowedTypes)) {
            throw new \InvalidArgumentException('Invalid intervention type: ' . $typeIntervention);
        }
        $this->typeIntervention = $typeIntervention;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
    {
        $allowedStatuts = [self::STATUT_EN_COURS, self::STATUT_TERMINEE, self::STATUT_ANNULEE];
        if ($statut !== null && !in_array($statut, $allowedStatuts)) {
            throw new \InvalidArgumentException('Invalid intervention status: ' . $statut);
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

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        if ($notes !== null && strlen($notes) > 500) {
            throw new \InvalidArgumentException('Notes cannot exceed 500 characters');
        }
        $this->notes = $notes;
        return $this;
    }

    public function getIdAdmin(): ?int
    {
        return $this->idAdmin;
    }

    public function setIdAdmin(?int $idAdmin): static
    {
        $this->idAdmin = $idAdmin;
        return $this;
    }
}