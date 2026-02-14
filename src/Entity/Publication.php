<?php

namespace App\Entity;

use App\Repository\PublicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;  // ✅ Corrigé (était Asse)

#[ORM\Entity(repositoryClass: PublicationRepository::class)]
class Publication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Le titre doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "Le contenu est obligatoire")]
    #[Assert\Length(
        min: 10,
        minMessage: "Le contenu doit contenir au moins {{ limit }} caractères"
    )]
    private ?string $contenu = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Type(
        type: \DateTimeInterface::class,
        message: "La date de publication doit être valide"
    )]
    private ?\DateTime $datePublication = null;

    #[ORM\ManyToOne(inversedBy: 'publications')]  // ✅ Changé de 'pubuser' à 'publications'
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "Vous devez sélectionner un utilisateur")]
    private ?Utilisateur $user = null;

    /**
     * @var Collection<int, Commentaire>
     */
    #[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: 'publication')]
    private Collection $pubCom;

    public function __construct()
    {
        $this->pubCom = new ArrayCollection();
        $this->datePublication = new \DateTime();  // ✅ Ajout pour initialiser la date
    }

    // ... tous les getters et setters restent identiques ...

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
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
            if ($pubCom->getPublication() === $this) {
                $pubCom->setPublication(null);
            }
        }
        return $this;
    }
}