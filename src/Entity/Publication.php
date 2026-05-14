<?php

namespace App\Entity;

use App\Repository\PublicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Asse;

#[ORM\Entity(repositoryClass: PublicationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Publication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $notificationMessage = null;

    // ✅ Fix: non-nullable bool
    #[ORM\Column]
    private bool $notificationRead = false;

    // ✅ Fix: non-nullable bool
    #[ORM\Column]
    private bool $isDeleted = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $notificationDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $categorie = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $pinnedAt = null;

    #[ORM\Column(type: 'integer')]
    private int $likesCount = 0;

    #[ORM\Column(type: 'integer')]
    private int $dislikesCount = 0;

    // ✅ Fix: non-nullable string
    #[ORM\Column(length: 255)]
    #[Asse\NotBlank(message: "Le titre est obligatoire")]
    #[Asse\Length(min: 3, max: 255, minMessage: "Le titre doit contenir au moins {{ limit }} caractères", maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères")]
    private string $titre;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $contenu = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $audioUrl = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $gifUrl = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $transcription = null;

    // ✅ Fix: DateTimeImmutable for timestamp
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $datePublication;

    // ✅ Fix: non-nullable relation
    #[ORM\ManyToOne(inversedBy: 'publications')]
    #[ORM\JoinColumn(nullable: false)]
    #[Asse\NotNull(message: "Vous devez sélectionner un utilisateur")]
    private Utilisateur $user;

    #[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: 'publication', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $pubCom;

    #[ORM\OneToMany(targetEntity: PublicationLike::class, mappedBy: 'publication', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $likes;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $reportReason = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $reportDescription = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $reportedAt = null;

    // ✅ Fix: non-nullable bool
    #[ORM\Column]
    private bool $isReported = false;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $reportStatus = 'pending';

    public function __construct()
    {
        $this->pubCom = new ArrayCollection();
        $this->likes = new ArrayCollection();
        // ✅ Fix: initialized in constructor
        $this->datePublication = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getTitre(): string { return $this->titre; }
    public function setTitre(string $titre): static { $this->titre = $titre; return $this; }

    public function getContenu(): ?string { return $this->contenu; }
    public function setContenu(?string $contenu): static { $this->contenu = $contenu; return $this; }

    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $image): static { $this->image = $image; return $this; }

    public function getAudioUrl(): ?string { return $this->audioUrl; }
    public function setAudioUrl(?string $audioUrl): static { $this->audioUrl = $audioUrl; return $this; }

    public function getGifUrl(): ?string { return $this->gifUrl; }
    public function setGifUrl(?string $gifUrl): static { $this->gifUrl = $gifUrl; return $this; }

    public function getTranscription(): ?string { return $this->transcription; }
    public function setTranscription(?string $transcription): static { $this->transcription = $transcription; return $this; }

    public function getDatePublication(): \DateTimeImmutable { return $this->datePublication; }

    // ✅ Fix: private setter
    public function setDatePublication(\DateTimeImmutable $datePublication): static { $this->datePublication = $datePublication; return $this; }

    public function getUser(): Utilisateur { return $this->user; }
    public function setUser(Utilisateur $user): static { $this->user = $user; return $this; }

    public function getNotificationMessage(): ?string { return $this->notificationMessage; }
    public function setNotificationMessage(?string $notificationMessage): static { $this->notificationMessage = $notificationMessage; return $this; }

    public function isNotificationRead(): bool { return $this->notificationRead; }
    public function setNotificationRead(bool $notificationRead): static { $this->notificationRead = $notificationRead; return $this; }

    public function isDeleted(): bool { return $this->isDeleted; }
    public function setIsDeleted(bool $isDeleted): static { $this->isDeleted = $isDeleted; return $this; }

    public function getNotificationDate(): ?\DateTimeImmutable { return $this->notificationDate; }
    public function setNotificationDate(?\DateTimeImmutable $notificationDate): static { $this->notificationDate = $notificationDate; return $this; }

    public function getCategorie(): ?string { return $this->categorie; }
    public function setCategorie(?string $categorie): static { $this->categorie = $categorie; return $this; }

    public function getPinnedAt(): ?\DateTimeImmutable { return $this->pinnedAt; }
    public function setPinnedAt(?\DateTimeImmutable $pinnedAt): static { $this->pinnedAt = $pinnedAt; return $this; }

    public function getLikesCount(): int { return $this->likesCount; }
    public function setLikesCount(int $likesCount): static { $this->likesCount = $likesCount; return $this; }

    public function getDislikesCount(): int { return $this->dislikesCount; }
    public function setDislikesCount(int $dislikesCount): static { $this->dislikesCount = $dislikesCount; return $this; }

    public function getPubCom(): Collection { return $this->pubCom; }

    public function addPubCom(Commentaire $pubCom): static
    {
        if (!$this->pubCom->contains($pubCom)) {
            $this->pubCom->add($pubCom);
            $pubCom->setPublication($this);
        }
        return $this;
    }

public function removePubCom(Commentaire $pubCom): static
{
    $this->pubCom->removeElement($pubCom);
    return $this;
}

    public function getReportReason(): ?string { return $this->reportReason; }
    public function setReportReason(?string $reportReason): static { $this->reportReason = $reportReason; return $this; }

    public function getReportDescription(): ?string { return $this->reportDescription; }
    public function setReportDescription(?string $reportDescription): static { $this->reportDescription = $reportDescription; return $this; }

    public function getReportedAt(): ?\DateTimeImmutable { return $this->reportedAt; }
    public function setReportedAt(?\DateTimeImmutable $reportedAt): static { $this->reportedAt = $reportedAt; return $this; }

    public function isReported(): bool { return $this->isReported; }
    public function setIsReported(bool $isReported): static { $this->isReported = $isReported; return $this; }

    public function getReportStatus(): ?string { return $this->reportStatus; }
    public function setReportStatus(?string $reportStatus): static { $this->reportStatus = $reportStatus; return $this; }
}
