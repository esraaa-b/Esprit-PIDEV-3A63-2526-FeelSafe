<?php

namespace App\Entity;

use App\Repository\RendezVousRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\ModeRendezVous;
use App\Enum\StatutRendezVous;

#[ORM\Entity(repositoryClass: RendezVousRepository::class)]
#[ORM\Table(name: 'rendez_vous')]
class RendezVous
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id')]
    private ?int $id = null;

    // ✅ Fix: non-nullable, DateTimeImmutable
    #[ORM\Column(name: 'date_rdv', type: 'date_immutable')]
    private \DateTimeImmutable $dateRdv;

    #[ORM\Column(name: 'heure_rdv', type: 'time_immutable')]
    private \DateTimeImmutable $heureRdv;

    // ✅ Fix: enumType already correct
    #[ORM\Column(name: 'mode', length: 20, enumType: ModeRendezVous::class)]
    private ModeRendezVous $mode;

    #[ORM\Column(name: 'localisation', length: 255, nullable: true)]
    private ?string $localisation = null;

    #[ORM\Column(name: 'statut', length: 20, enumType: StatutRendezVous::class)]
    private StatutRendezVous $statut;

    #[ORM\Column(name: 'commentaire', length: 255, nullable: true)]
    private ?string $commentaire = null;

    // ✅ Fix: DateTimeImmutable, non-nullable
    #[ORM\Column(name: 'date_creation', type: 'datetime_immutable')]
    private \DateTimeImmutable $dateCreation;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'utilisateur_id', referencedColumnName: 'id')]
    private ?Utilisateur $utilisateur = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'professionnel_id', referencedColumnName: 'id')]
    private ?Utilisateur $professionnel = null;

    #[ORM\OneToMany(mappedBy: 'rendezvous', targetEntity: Accompagnement::class)]
    private Collection $accomp;

    public function __construct()
    {
        $this->accomp = new ArrayCollection();
        // ✅ Fix: initialized in constructor
        $this->dateCreation = new \DateTimeImmutable();
        $this->dateRdv = new \DateTimeImmutable();
        $this->heureRdv = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getDateRdv(): \DateTimeImmutable { return $this->dateRdv; }
    public function setDateRdv(\DateTimeImmutable $dateRdv): static { $this->dateRdv = $dateRdv; return $this; }

    public function getHeureRdv(): \DateTimeImmutable { return $this->heureRdv; }
    public function setHeureRdv(\DateTimeImmutable $heureRdv): static { $this->heureRdv = $heureRdv; return $this; }

    public function getMode(): ModeRendezVous { return $this->mode; }
    public function setMode(ModeRendezVous $mode): static { $this->mode = $mode; return $this; }

    public function getLocalisation(): ?string { return $this->localisation; }
    public function setLocalisation(?string $localisation): static { $this->localisation = $localisation; return $this; }

    public function getStatut(): StatutRendezVous { return $this->statut; }
    public function setStatut(StatutRendezVous $statut): static { $this->statut = $statut; return $this; }

    public function getCommentaire(): ?string { return $this->commentaire; }
    public function setCommentaire(?string $commentaire): static { $this->commentaire = $commentaire; return $this; }

    public function getDateCreation(): \DateTimeImmutable { return $this->dateCreation; }

    // ✅ Fix: private setter
    private function setDateCreation(\DateTimeImmutable $dateCreation): static { $this->dateCreation = $dateCreation; return $this; }

    public function getUtilisateur(): ?Utilisateur { return $this->utilisateur; }
    public function setUtilisateur(?Utilisateur $utilisateur): static { $this->utilisateur = $utilisateur; return $this; }

    public function getProfessionnel(): ?Utilisateur { return $this->professionnel; }
    public function setProfessionnel(?Utilisateur $professionnel): static { $this->professionnel = $professionnel; return $this; }

    public function getAccomp(): Collection { return $this->accomp; }

    public function addAccomp(Accompagnement $accomp): static
    {
        if (!$this->accomp->contains($accomp)) {
            $this->accomp->add($accomp);
            $accomp->setRendezvous($this);
        }
        return $this;
    }

    public function removeAccomp(Accompagnement $accomp): static
    {
        if ($this->accomp->removeElement($accomp)) {
            if ($accomp->getRendezvous() === $this) {
                $accomp->setRendezvous(null);
            }
        }
        return $this;
    }
}