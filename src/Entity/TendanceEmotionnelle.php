<?php

namespace App\Entity;

use App\Repository\TendanceEmotionnelleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TendanceEmotionnelleRepository::class)]
class TendanceEmotionnelle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $mois = null;

    #[ORM\Column]
    private ?int $annee = null;

    #[ORM\Column(length: 20)]
    private ?string $emotion = null;

    #[ORM\Column]
    private ?int $totaleOccurrences = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $pourcentage = null;

    #[ORM\Column]
    private ?\DateTime $dateCalcul = null;

    #[ORM\ManyToOne(inversedBy: 'usertend')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $utilisateur = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMois(): ?int
    {
        return $this->mois;
    }

    public function setMois(int $mois): static
    {
        $this->mois = $mois;

        return $this;
    }

    public function getAnnee(): ?int
    {
        return $this->annee;
    }

    public function setAnnee(int $annee): static
    {
        $this->annee = $annee;

        return $this;
    }

    public function getEmotion(): ?string
    {
        return $this->emotion;
    }

    public function setEmotion(string $emation): static
    {
        $this->emotion = $emation;

        return $this;
    }

    public function getTotaleOccurrences(): ?int
    {
        return $this->totaleOccurrences;
    }

    public function setTotaleOccurrences(int $totaleOccurrences): static
    {
        $this->totaleOccurrences = $totaleOccurrences;

        return $this;
    }

    public function getPourcentage(): ?string
    {
        return $this->pourcentage;
    }

    public function setPourcentage(string $pourcentage): static
    {
        $this->pourcentage = $pourcentage;

        return $this;
    }

    public function getDateCalcul(): ?\DateTime
    {
        return $this->dateCalcul;
    }

    public function setDateCalcul(\DateTime $dateCalcul): static
    {
        $this->dateCalcul = $dateCalcul;

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
}
