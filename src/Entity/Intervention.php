<?php

namespace App\Entity;

use App\Repository\InterventionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InterventionRepository::class)]
class Intervention
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $interventionType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $result = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $interventionDate = null;

    #[ORM\ManyToOne(inversedBy: 'urginter')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Urgence $urgence = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInterventionType(): ?string
    {
        return $this->interventionType;
    }

    public function setInterventionType(?string $interventionType): static
    {
        $this->interventionType = $interventionType;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getResult(): ?string
    {
        return $this->result;
    }

    public function setResult(?string $result): static
    {
        $this->result = $result;

        return $this;
    }

    public function getInterventionDate(): ?\DateTime
    {
        return $this->interventionDate;
    }

    public function setInterventionDate(?\DateTime $interventionDate): static
    {
        $this->interventionDate = $interventionDate;

        return $this;
    }

    public function getUrgence(): ?Urgence
    {
        return $this->urgence;
    }

    public function setUrgence(?Urgence $urgence): static
    {
        $this->urgence = $urgence;

        return $this;
    }
}
