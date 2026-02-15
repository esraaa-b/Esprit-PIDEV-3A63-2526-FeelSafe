<?php

namespace App\Entity;

use App\Repository\CommentaireRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Asse;

#[ORM\Entity(repositoryClass: CommentaireRepository::class)]
class Commentaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Asse\NotBlank(message: "Le contenu est obligatoire")]
    private ?string $contenu = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $gifUrl = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $dateCommentaire = null;

    #[ORM\ManyToOne(inversedBy: 'pubCom')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Publication $publication = null;

    #[ORM\ManyToOne(inversedBy: 'userCom')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $user = null;

    // Self-referencing: parent comment (null for root comments)
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'replies')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?self $parent = null;

    // Self-referencing: child replies
    /**
     * @var Collection<int, Commentaire>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent', cascade: ['remove'], orphanRemoval: true)]
    private Collection $replies;

    #[ORM\Column(type: 'integer')]
    private int $likesCount = 0;

    #[ORM\Column(type: 'integer')]
    private int $dislikesCount = 0;

    /**
     * @var Collection<int, CommentaireLike>
     */
    #[ORM\OneToMany(targetEntity: CommentaireLike::class, mappedBy: 'commentaire', cascade: ['remove'], orphanRemoval: true)]
    private Collection $likes;

    public function __construct()
    {
        $this->replies = new ArrayCollection();
        $this->likes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function getGifUrl(): ?string
    {
        return $this->gifUrl;
    }

    public function setGifUrl(?string $gifUrl): static
    {
        $this->gifUrl = $gifUrl;

        return $this;
    }

    public function getDateCommentaire(): ?\DateTime
    {
        return $this->dateCommentaire;
    }

    public function setDateCommentaire(?\DateTime $dateCommentaire): static
    {
        $this->dateCommentaire = $dateCommentaire;

        return $this;
    }

    public function getPublication(): ?Publication
    {
        return $this->publication;
    }

    public function setPublication(?Publication $publication): static
    {
        $this->publication = $publication;

        return $this;
    }

    public function getUser(): ?Utilisateur
    {
        return $this->user;
    }

    public function setUser(?Utilisateur $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, Commentaire>
     */
    public function getReplies(): Collection
    {
        return $this->replies;
    }

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

    public function getLikesCount(): int
    {
        return $this->likesCount;
    }

    public function setLikesCount(int $likesCount): static
    {
        $this->likesCount = $likesCount;

        return $this;
    }

    public function getDislikesCount(): int
    {
        return $this->dislikesCount;
    }

    public function setDislikesCount(int $dislikesCount): static
    {
        $this->dislikesCount = $dislikesCount;

        return $this;
    }

    /**
     * @return Collection<int, CommentaireLike>
     */
    public function getLikes(): Collection
    {
        return $this->likes;
    }

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
        if ($this->likes->removeElement($like)) {
            if ($like->getCommentaire() === $this) {
                $like->setCommentaire(null);
            }
        }

        return $this;
    }
}
