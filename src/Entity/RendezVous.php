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
class RendezVous
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateRdv = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTime $heureRdv = null;

    #[ORM\Column(length: 20,enumType: ModeRendezVous::class)]
    private ModeRendezVous $mode;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $localisation = null;

    #[ORM\Column(length: 20, enumType: StatutRendezVous::class)]
    private StatutRendezVous $statut;
    
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column]
    private ?\DateTime $dateCreation = null;

    #[ORM\ManyToOne(inversedBy: 'rendezVousClients')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $utilisateur = null;

    #[ORM\ManyToOne(inversedBy: 'rendezVousProfessionnels')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $professionnel = null;

    /**
     * @var Collection<int, Accompagnement>
     */
    #[ORM\OneToMany(targetEntity: Accompagnement::class, mappedBy: 'rendezvous')]
    private Collection $accomp;

    public function __construct()
    {
        $this->accomp = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateRdv(): ?\DateTime
    {
        return $this->dateRdv;
    }

    public function setDateRdv(\DateTime $dateRdv): static
    {
        $this->dateRdv = $dateRdv;

        return $this;
    }

    public function getHeureRdv(): ?\DateTime
    {
        return $this->heureRdv;
    }

    public function setHeureRdv(\DateTime $heureRdv): static
    {
        $this->heureRdv = $heureRdv;

        return $this;
    }

   public function getMode(): ModeRendezVous
    {
        return $this->mode;
    }

    public function setMode(ModeRendezVous $mode): static
    {
        $this->mode = $mode;
        return $this;
    }

    public function getLocalisation(): ?string
    {
        return $this->localisation;
    }

    public function setLocalisation(?string $localisation): static
    {
        $this->localisation = $localisation;

        return $this;
    }

   public function getStatut(): StatutRendezVous
    {
        return $this->statut;
    }

    public function setStatut(StatutRendezVous $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

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

    public function getProfessionnel(): ?Utilisateur
    {
        return $this->professionnel;
    }

    public function setProfessionnel(?Utilisateur $professionnel): static
    {
        $this->professionnel = $professionnel;

        return $this;
    }

    /**
     * @return Collection<int, Accompagnement>
     */
    public function getAccomp(): Collection
    {
        return $this->accomp;
    }

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
            // set the owning side to null (unless already changed)
            if ($accomp->getRendezvous() === $this) {
                $accomp->setRendezvous(null);
            }
        }

        return $this;
    }
}
