<?php

namespace App\Entity;

use App\Repository\PublicationLikeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PublicationLikeRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_user_publication_vote', columns: ['publication_id', 'user_id'])]
class PublicationLike
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Publication::class, inversedBy: 'likes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Publication $publication;
    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Utilisateur $user;

    #[ORM\Column(length: 10)]
    private string $type; // 'like' or 'dislike'

    public function getId(): ?int { return $this->id; }

    public function getPublication(): Publication { return $this->publication; }
    public function setPublication(Publication $publication): static { $this->publication = $publication; return $this; }

    public function getUser(): Utilisateur { return $this->user; }
    public function setUser(Utilisateur $user): static { $this->user = $user; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }
}
