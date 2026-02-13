<?php

namespace App\Entity;

use App\Repository\PublicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PublicationRepository::class)]
class Publication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $contenu = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $datePublication = null;

    #[ORM\ManyToOne(inversedBy: 'pubuser')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $user = null;

    /**
     * @var Collection<int, Commentaire>
     */
    #[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: 'publication')]
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
            // set the owning side to null (unless already changed)
            if ($pubCom->getPublication() === $this) {
                $pubCom->setPublication(null);
            }
        }

        return $this;
    }
}
