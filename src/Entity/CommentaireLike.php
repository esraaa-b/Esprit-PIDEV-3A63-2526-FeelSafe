<?php

namespace App\Entity;

use App\Repository\CommentaireLikeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommentaireLikeRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_user_commentaire_vote', columns: ['commentaire_id', 'user_id'])]
class CommentaireLike
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Commentaire::class, inversedBy: 'likes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Commentaire $commentaire = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $user = null;

    #[ORM\Column(length: 10)]
    private ?string $type = null; // 'like' or 'dislike'

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommentaire(): ?Commentaire
    {
        return $this->commentaire;
    }

    public function setCommentaire(?Commentaire $commentaire): static
    {
        $this->commentaire = $commentaire;
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }
}
