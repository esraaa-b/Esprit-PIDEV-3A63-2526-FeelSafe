<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'disponibilite')]
class Disponibilite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // ✅ Fix: non-nullable relation
    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Utilisateur $professionnel;

    // ✅ Fix: non-nullable, initialized in constructor
    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $date;

    #[ORM\Column(type: 'time_immutable')]
    private \DateTimeImmutable $heureDebut;

    #[ORM\Column(type: 'time_immutable')]
    private \DateTimeImmutable $heureFin;

    public function __construct()
    {
        $this->date = new \DateTimeImmutable();
        $this->heureDebut = new \DateTimeImmutable();
        $this->heureFin = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getProfessionnel(): Utilisateur { return $this->professionnel; }
    public function setProfessionnel(Utilisateur $professionnel): static { $this->professionnel = $professionnel; return $this; }

    public function getDate(): \DateTimeImmutable { return $this->date; }
    public function setDate(\DateTimeImmutable $date): static { $this->date = $date; return $this; }

    public function getHeureDebut(): \DateTimeImmutable { return $this->heureDebut; }
    public function setHeureDebut(\DateTimeImmutable $heureDebut): static { $this->heureDebut = $heureDebut; return $this; }

    public function getHeureFin(): \DateTimeImmutable { return $this->heureFin; }
    public function setHeureFin(\DateTimeImmutable $heureFin): static { $this->heureFin = $heureFin; return $this; }
}