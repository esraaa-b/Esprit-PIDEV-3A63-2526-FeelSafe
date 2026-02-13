<?php

namespace App\Entity;

use App\Repository\AccompagnementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\ProchainRdv;


#[ORM\Entity(repositoryClass: AccompagnementRepository::class)]
class Accompagnement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 5, enumType: ProchainRdv::class)]
    private ProchainRdv $prochainRdv;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $dateProchainRdv = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $objectifs = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notesSuivi = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $niveauPriorite = null;

    #[ORM\ManyToOne(inversedBy: 'accomp')]
    #[ORM\JoinColumn(nullable: false)]
    private ?RendezVous $rendezvous = null;

    #[ORM\ManyToOne(inversedBy: 'utiliaccomp')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $utilisateur = null;

    public function getId(): ?int
    {
        return $this->id;
    }

   public function getProchainRdv(): ProchainRdv
    {
        return $this->prochainRdv;
    }

    public function setProchainRdv(ProchainRdv $prochainRdv): static
    {
        $this->prochainRdv = $prochainRdv;
        return $this;
    }


    public function getDateProchainRdv(): ?\DateTime
    {
        return $this->dateProchainRdv;
    }

    public function setDateProchainRdv(?\DateTime $dateProchainRdv): static
    {
        $this->dateProchainRdv = $dateProchainRdv;

        return $this;
    }

    public function getObjectifs(): ?string
    {
        return $this->objectifs;
    }

    public function setObjectifs(?string $objectifs): static
    {
        $this->objectifs = $objectifs;

        return $this;
    }

    public function getNotesSuivi(): ?string
    {
        return $this->notesSuivi;
    }

    public function setNotesSuivi(?string $notesSuivi): static
    {
        $this->notesSuivi = $notesSuivi;

        return $this;
    }

    public function getNiveauPriorite(): ?int
    {
        return $this->niveauPriorite;
    }

    public function setNiveauPriorite(?int $niveauPriorite): static
    {
        $this->niveauPriorite = $niveauPriorite;

        return $this;
    }

    public function getRendezvous(): ?RendezVous
    {
        return $this->rendezvous;
    }

    public function setRendezvous(?RendezVous $rendezvous): static
    {
        $this->rendezvous = $rendezvous;

        return $this;
    }

    public function getUtilisateur(): ?User
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?User $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }
}
