<?php

namespace App\Entity;

use App\Repository\UrgenceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UrgenceRepository::class)]
class Urgence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $typeUrgence = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $severityLevel = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $location = null;

    // ✅ Fix: DateTimeImmutable, non-nullable, initialized in constructor
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    // ✅ Fix: non-nullable relation
    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private Utilisateur $user;

    #[ORM\OneToMany(targetEntity: Intervention::class, mappedBy: 'urgence')]
    private Collection $urginter;

    public function __construct()
    {
        $this->urginter = new ArrayCollection();
        // ✅ Fix: initialized in constructor
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getTypeUrgence(): ?string { return $this->typeUrgence; }
    public function setTypeUrgence(?string $typeUrgence): static { $this->typeUrgence = $typeUrgence; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getSeverityLevel(): ?int { return $this->severityLevel; }
    public function setSeverityLevel(?int $severityLevel): static { $this->severityLevel = $severityLevel; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(?string $status): static { $this->status = $status; return $this; }

    public function getLocation(): ?string { return $this->location; }
    public function setLocation(?string $location): static { $this->location = $location; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    // ✅ Fix: private setter
    private function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getUser(): Utilisateur { return $this->user; }
    public function setUser(Utilisateur $user): static { $this->user = $user; return $this; }

    public function getUrginter(): Collection { return $this->urginter; }

    public function addUrginter(Intervention $urginter): static
    {
        if (!$this->urginter->contains($urginter)) {
            $this->urginter->add($urginter);
            $urginter->setUrgence($this);
        }
        return $this;
    }
    
public function removeUrginter(Intervention $urginter): static
{
    $this->urginter->removeElement($urginter);
    return $this;
}
}