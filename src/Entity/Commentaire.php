<?php

namespace App\Entity;

use App\Repository\CommentaireRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Asse;

#[ORM\Entity(repositoryClass: CommentaireRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Commentaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // ✅ Fix: non-nullable string (contenu is required)
    #[ORM\Column(type: Types::TEXT)]
    #[Asse\NotBlank(message: "Le contenu est obligatoire")]
    private string $contenu;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $gifUrl = null;

    // ✅ Fix: DateTimeImmutable, initialized in constructor
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $dateCommentaire;

    // ✅ Fix: non-nullable relation
    #[ORM\ManyToOne(inversedBy: 'pubCom')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Publication $publication;

    // ✅ Fix: non-nullable relation
    #[ORM\ManyToOne(inversedBy: 'userCom')]
    #[ORM\JoinColumn(nullable: false)]
    private Utilisateur $user;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'replies')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?self $parent = null;

    // ✅ Fix: added cascade persist
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent', cascade: ['persist', 'remove'])]
    private Collection $replies;

    #[ORM\Column(type: 'integer')]
    private int $likesCount = 0;

    #[ORM\Column(type: 'integer')]
    private int $dislikesCount = 0;

    // ✅ Fix: added cascade persist
    #[ORM\OneToMany(targetEntity: CommentaireLike::class, mappedBy: 'commentaire', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $likes;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $reportReason = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $reportDescription = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $reportedAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isReported = false;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $reportStatus = 'pending';

    public function __construct()
    {
        $this->replies = new ArrayCollection();
        $this->likes = new ArrayCollection();
        // ✅ Fix: initialized in constructor
        $this->dateCommentaire = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getContenu(): string { return $this->contenu; }
    public function setContenu(string $contenu): static { $this->contenu = $contenu; return $this; }

    public function getGifUrl(): ?string { return $this->gifUrl; }
    public function setGifUrl(?string $gifUrl): static { $this->gifUrl = $gifUrl; return $this; }

    public function getDateCommentaire(): \DateTimeImmutable { return $this->dateCommentaire; }

    // ✅ Fix: private setter
    public function setDateCommentaire(\DateTimeImmutable $dateCommentaire): static { $this->dateCommentaire = $dateCommentaire; return $this; }

    public function getPublication(): Publication { return $this->publication; }
    public function setPublication(Publication $publication): static { $this->publication = $publication; return $this; }

    public function getUser(): Utilisateur { return $this->user; }
    public function setUser(Utilisateur $user): static { $this->user = $user; return $this; }

    public function getParent(): ?self { return $this->parent; }
    public function setParent(?self $parent): static { $this->parent = $parent; return $this; }

    public function getReplies(): Collection { return $this->replies; }

    public function addReply(self $reply): static
    {
        if (!$this->replies->contains($reply)) {
            $this->replies->add($reply);
            $reply->setParent($this);
        }
        return $this;
    }

    public function removeReply(self $reply): static
    {
        if ($this->replies->removeElement($reply)) {
            if ($reply->getParent() === $this) {
                $reply->setParent(null);
            }
        }
        return $this;
    }

    public function getLikesCount(): int { return $this->likesCount; }
    public function setLikesCount(int $likesCount): static { $this->likesCount = $likesCount; return $this; }

    public function getDislikesCount(): int { return $this->dislikesCount; }
    public function setDislikesCount(int $dislikesCount): static { $this->dislikesCount = $dislikesCount; return $this; }

    public function getLikes(): Collection { return $this->likes; }

    public function addLike(CommentaireLike $like): static
    {
        if (!$this->likes->contains($like)) {
            $this->likes->add($like);
            $like->setCommentaire($this);
        }
        return $this;
    }

public function removeLike(CommentaireLike $like): static
{
    $this->likes->removeElement($like);
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