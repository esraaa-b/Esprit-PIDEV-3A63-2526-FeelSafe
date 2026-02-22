<?php

namespace App\Entity;

use App\Repository\SessionActiviteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\StatutSession;
use App\Enum\HumeurEnum;
use App\Enum\ImpactPercu;
use App\Enum\FrequenceSouhaitee;
use App\Enum\RecommandePar;

#[ORM\Entity(repositoryClass: SessionActiviteRepository::class)]
class SessionActivite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'sessionsActivites')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $utilisateur = null;

    #[ORM\ManyToOne(inversedBy: 'sessions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ActiviteBienEtre $activite = null;

    #[ORM\Column]
    private ?\DateTime $dateDebut = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $dateFin = null;

    #[ORM\Column(nullable: true)]
    private ?int $dureeReelle = null;

    #[ORM\Column(length: 20, enumType: StatutSession::class)]
    private ?StatutSession $statutSession = null;

    #[ORM\Column(length: 20, enumType: HumeurEnum::class, nullable: true)]
    private ?HumeurEnum $humeurAvant = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $scoreHumeurAvant = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $emotionAvant = null;

    #[ORM\Column(length: 20, enumType: HumeurEnum::class, nullable: true)]
    private ?HumeurEnum $humeurApres = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $scoreHumeurApres = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $emotionApres = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $noteSatisfaction = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(length: 20, enumType: ImpactPercu::class, nullable: true)]
    private ?ImpactPercu $impactPercu = null;

    #[ORM\Column(nullable: true)]
    private ?bool $estObjectifAtteint = null;

    #[ORM\Column(length: 20, enumType: FrequenceSouhaitee::class, nullable: true)]
    private ?FrequenceSouhaitee $frequenceSouhaitee = null;

    #[ORM\Column(length: 20, enumType: RecommandePar::class, nullable: true)]
    private ?RecommandePar $recommandePar = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 2, nullable: true)]
    private ?string $scoreRecommandationIa = null;

    public function __construct()
    {
        $this->dateDebut = new \DateTime();
        $this->statutSession = StatutSession::EN_COURS;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getActivite(): ?ActiviteBienEtre
    {
        return $this->activite;
    }

    public function setActivite(?ActiviteBienEtre $activite): static
    {
        $this->activite = $activite;
        return $this;
    }

    public function getDateDebut(): ?\DateTime
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTime $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTime
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTime $dateFin): static
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getDureeReelle(): ?int
    {
        return $this->dureeReelle;
    }

    public function setDureeReelle(?int $dureeReelle): static
    {
        $this->dureeReelle = $dureeReelle;
        return $this;
    }

    public function getStatutSession(): ?StatutSession
    {
        return $this->statutSession;
    }

    public function setStatutSession(StatutSession $statutSession): static
    {
        $this->statutSession = $statutSession;
        return $this;
    }

    public function getHumeurAvant(): ?HumeurEnum
    {
        return $this->humeurAvant;
    }

    public function setHumeurAvant(?HumeurEnum $humeurAvant): static
    {
        $this->humeurAvant = $humeurAvant;
        return $this;
    }

    public function getScoreHumeurAvant(): ?int
    {
        return $this->scoreHumeurAvant;
    }

    public function setScoreHumeurAvant(?int $scoreHumeurAvant): static
    {
        $this->scoreHumeurAvant = $scoreHumeurAvant;
        return $this;
    }

    public function getEmotionAvant(): ?string
    {
        return $this->emotionAvant;
    }

    public function setEmotionAvant(?string $emotionAvant): static
    {
        $this->emotionAvant = $emotionAvant;
        return $this;
    }

    public function getHumeurApres(): ?HumeurEnum
    {
        return $this->humeurApres;
    }

    public function setHumeurApres(?HumeurEnum $humeurApres): static
    {
        $this->humeurApres = $humeurApres;
        return $this;
    }

    public function getScoreHumeurApres(): ?int
    {
        return $this->scoreHumeurApres;
    }

    public function setScoreHumeurApres(?int $scoreHumeurApres): static
    {
        $this->scoreHumeurApres = $scoreHumeurApres;
        return $this;
    }

    public function getEmotionApres(): ?string
    {
        return $this->emotionApres;
    }

    public function setEmotionApres(?string $emotionApres): static
    {
        $this->emotionApres = $emotionApres;
        return $this;
    }

    public function getNoteSatisfaction(): ?int
    {
        return $this->noteSatisfaction;
    }

    public function setNoteSatisfaction(?int $noteSatisfaction): static
    {
        $this->noteSatisfaction = $noteSatisfaction;
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

    public function getImpactPercu(): ?ImpactPercu
    {
        return $this->impactPercu;
    }

    public function setImpactPercu(?ImpactPercu $impactPercu): static
    {
        $this->impactPercu = $impactPercu;
        return $this;
    }

    public function isEstObjectifAtteint(): ?bool
    {
        return $this->estObjectifAtteint;
    }

    public function setEstObjectifAtteint(?bool $estObjectifAtteint): static
    {
        $this->estObjectifAtteint = $estObjectifAtteint;
        return $this;
    }

    public function getFrequenceSouhaitee(): ?FrequenceSouhaitee
    {
        return $this->frequenceSouhaitee;
    }

    public function setFrequenceSouhaitee(?FrequenceSouhaitee $frequenceSouhaitee): static
    {
        $this->frequenceSouhaitee = $frequenceSouhaitee;
        return $this;
    }

    public function getRecommandePar(): ?RecommandePar
    {
        return $this->recommandePar;
    }

    public function setRecommandePar(?RecommandePar $recommandePar): static
    {
        $this->recommandePar = $recommandePar;
        return $this;
    }

    public function getScoreRecommandationIa(): ?string
    {
        return $this->scoreRecommandationIa;
    }

    public function setScoreRecommandationIa(?string $scoreRecommandationIa): static
    {
        $this->scoreRecommandationIa = $scoreRecommandationIa;
        return $this;
    }
}