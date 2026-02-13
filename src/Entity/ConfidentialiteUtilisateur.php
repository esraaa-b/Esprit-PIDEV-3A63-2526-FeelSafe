<?php

namespace App\Entity;

use App\Repository\ConfidentialiteUtilisateurRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConfidentialiteUtilisateurRepository::class)]
class ConfidentialiteUtilisateur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?bool $partageDonnees = null;

    #[ORM\Column(nullable: true)]
    private ?bool $notificationsEmail = null;

    #[ORM\Column(nullable: true)]
    private ?bool $visibiliteProfil = null;

    #[ORM\Column]
    private ?\DateTime $dateModification = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $utilisateur = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isPartageDonnees(): ?bool
    {
        return $this->partageDonnees;
    }

    public function setPartageDonnees(?bool $partageDonnees): static
    {
        $this->partageDonnees = $partageDonnees;

        return $this;
    }

    public function isNotificationsEmail(): ?bool
    {
        return $this->notificationsEmail;
    }

    public function setNotificationsEmail(?bool $notificationsEmail): static
    {
        $this->notificationsEmail = $notificationsEmail;

        return $this;
    }

    public function isVisibiliteProfil(): ?bool
    {
        return $this->visibiliteProfil;
    }

    public function setVisibiliteProfil(?bool $visibiliteProfil): static
    {
        $this->visibiliteProfil = $visibiliteProfil;

        return $this;
    }

    public function getDateModification(): ?\DateTime
    {
        return $this->dateModification;
    }

    public function setDateModification(\DateTime $dateModification): static
    {
        $this->dateModification = $dateModification;

        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }
}
