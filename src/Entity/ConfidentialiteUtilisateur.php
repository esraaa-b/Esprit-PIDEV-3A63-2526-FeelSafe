<?php

namespace App\Entity;

use App\Repository\ConfidentialiteUtilisateurRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConfidentialiteUtilisateurRepository::class)]
#[ORM\Table(name: 'confidentialite_utilisateur')]
#[ORM\HasLifecycleCallbacks] // ← AJOUT IMPORTANT
class ConfidentialiteUtilisateur
{
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?bool $partageDonnees = false;

    #[ORM\Column]
    private ?bool $notificationsEmail = true;

    #[ORM\Column(length: 20)]
    private ?string $visibiliteProfil = 'prive';

    #[ORM\Column]
    private ?\DateTimeImmutable $dateModification = null;

    #[ORM\OneToOne(inversedBy: 'confidentialite', targetEntity: Utilisateur::class)]
    private ?Utilisateur $utilisateur = null;


    public function __construct()
    {
        $this->dateModification = new \DateTimeImmutable();
        $this->partageDonnees = false;
        $this->notificationsEmail = true;
        $this->visibiliteProfil = 'prive';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isPartageDonnees(): ?bool
    {
        return $this->partageDonnees;
    }

    public function setPartageDonnees(bool $partageDonnees): static
    {
        $this->partageDonnees = $partageDonnees;
        return $this;
    }

    public function isNotificationsEmail(): ?bool
    {
        return $this->notificationsEmail;
    }

    public function setNotificationsEmail(bool $notificationsEmail): static
    {
        $this->notificationsEmail = $notificationsEmail;
        return $this;
    }

    public function getVisibiliteProfil(): ?string
    {
        return $this->visibiliteProfil;
    }

    public function setVisibiliteProfil(string $visibiliteProfil): static
    {
        $this->visibiliteProfil = $visibiliteProfil;
        return $this;
    }

    public function getDateModification(): ?\DateTimeImmutable
    {
        return $this->dateModification;
    }

    public function setDateModification(\DateTimeImmutable $dateModification): static
    {
        $this->dateModification = $dateModification;
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

    // NOUVELLE MÉTHODE AJOUTÉE
    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->dateModification = new \DateTimeImmutable();
    }
}