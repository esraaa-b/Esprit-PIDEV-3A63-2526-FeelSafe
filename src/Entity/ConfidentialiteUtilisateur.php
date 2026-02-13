<?php

namespace App\Entity;

use App\Repository\ConfidentialiteUtilisateurRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConfidentialiteUtilisateurRepository::class)]
#[ORM\Table(name: 'confidentialite_utilisateur')]
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

    #[ORM\OneToOne(inversedBy: 'confidentialite', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $utilisateur = null;

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
        $this->updateDateModification();
        return $this;
    }

    public function isNotificationsEmail(): ?bool
    {
        return $this->notificationsEmail;
    }

    public function setNotificationsEmail(bool $notificationsEmail): static
    {
        $this->notificationsEmail = $notificationsEmail;
        $this->updateDateModification();
        return $this;
    }

    public function getVisibiliteProfil(): ?string
    {
        return $this->visibiliteProfil;
    }

    public function setVisibiliteProfil(string $visibiliteProfil): static
    {
        $this->visibiliteProfil = $visibiliteProfil;
        $this->updateDateModification();
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

    private function updateDateModification(): void
    {
        $this->dateModification = new \DateTimeImmutable();
    }

    public function getUtilisateur(): ?User
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(User $utilisateur): static
    {
        $this->utilisateur = $utilisateur;
        return $this;
    }
}