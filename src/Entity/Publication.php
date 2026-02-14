<?php

namespace App\Entity;

use App\Repository\PublicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Asse;

#[ORM\Entity(repositoryClass: PublicationRepository::class)]
class Publication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $notificationMessage = null;

    #[ORM\Column]
    private ?bool $notificationRead = false;

    #[ORM\Column]
    private ?bool $isDeleted = false;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $notificationDate = null;

    public function getNotificationMessage(): ?string
    {
        return $this->notificationMessage;
    }

    public function setNotificationMessage(?string $notificationMessage): static
    {
        $this->notificationMessage = $notificationMessage;

        return $this;
    }

    public function isNotificationRead(): ?bool
    {
        return $this->notificationRead;
    }

    public function setNotificationRead(bool $notificationRead): static
    {
        $this->notificationRead = $notificationRead;

        return $this;
    }

    public function isDeleted(): ?bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted): static
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    public function getNotificationDate(): ?\DateTimeInterface
    {
        return $this->notificationDate;
    }

    public function setNotificationDate(?\DateTimeInterface $notificationDate): static
    {
        $this->notificationDate = $notificationDate;

        return $this;
    }

    #[ORM\Column(type: 'integer')]
    private int $likesCount = 0;

    #[ORM\Column(type: 'integer')]
    private int $dislikesCount = 0;

    #[ORM\Column(length: 255)]
    #[Asse\NotBlank(message: "Le titre est obligatoire")]
    #[Asse\Length(
        min: 3,
        max: 255,
        minMessage: "Le titre doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Asse\NotBlank(message: "Le contenu est obligatoire")]
    #[Asse\Length(
        min: 10,
        minMessage: "Le contenu doit contenir au moins {{ limit }} caractères"
    )]
    private ?string $contenu = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(nullable: true)]
    #[Asse\Type(
        type: \DateTimeInterface::class,
        message: "La date de publication doit être valide"
    )]

    private ?\DateTime $datePublication = null;

    #[ORM\ManyToOne(inversedBy: 'pubuser')]
    #[ORM\JoinColumn(nullable: false)]
    #[Asse\NotNull(message: "Vous devez sélectionner un utilisateur")]
    private ?Utilisateur $user = null;

    /**
     * @var Collection<int, Commentaire>
     */
    #[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: 'publication', cascade: ['remove'], orphanRemoval: true)]
    private Collection $pubCom;


    public function __construct()
    {
        $this->pubCom = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(?string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getDatePublication(): ?\DateTime
    {
        return $this->datePublication;
    }

    public function setDatePublication(?\DateTime $datePublication): static
    {
        $this->datePublication = $datePublication;

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
    public function getLikesCount(): int
{
    return $this->likesCount;
}

public function setLikesCount(int $likesCount): self
{
    $this->likesCount = $likesCount;
    return $this;
}

public function getDislikesCount(): int
{
    return $this->dislikesCount;
}

public function setDislikesCount(int $dislikesCount): self
{
    $this->dislikesCount = $dislikesCount;
    return $this;
}

    /**
     * @return Collection<int, Commentaire>
     */
    public function getPubCom(): Collection
    {
        return $this->pubCom;
    }

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
        if ($this->pubCom->removeElement($pubCom)) {
            // set the owning side to null (unless already changed)
            if ($pubCom->getPublication() === $this) {
                $pubCom->setPublication(null);
            }
        }

        return $this;
    }
}
