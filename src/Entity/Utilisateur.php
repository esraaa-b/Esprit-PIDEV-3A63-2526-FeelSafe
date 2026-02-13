<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\RoleUtilisateur;
use App\Enum\StatutUtilisateur;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface

{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    private ?string $prenom = null;

    #[ORM\Column(length: 150)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $motDePasse = null;

    #[ORM\Column(length: 30, enumType: RoleUtilisateur::class)]
    private RoleUtilisateur $role;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 20, enumType: StatutUtilisateur::class)]
    private StatutUtilisateur $statut;

    #[ORM\Column]
    private ?\DateTime $dateCreation = null;

    /**
     * @var Collection<int, RendezVous>
     */
    #[ORM\OneToMany(targetEntity: RendezVous::class, mappedBy: 'utilisateur')]
    private Collection $rendezVousClients;

    /**
     * @var Collection<int, RendezVous>
     */
    #[ORM\OneToMany(targetEntity: RendezVous::class, mappedBy: 'professionnel')]
    private Collection $rendezVousProfessionnels;

    /**
     * @var Collection<int, Accompagnement>
     */
    #[ORM\OneToMany(targetEntity: Accompagnement::class, mappedBy: 'utilisateur')]
    private Collection $utiliaccomp;

    /**
     * @var Collection<int, Publication>
     */
    #[ORM\OneToMany(targetEntity: Publication::class, mappedBy: 'user')]
    private Collection $pubuser;

    /**
     * @var Collection<int, Commentaire>
     */
    #[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: 'user')]
    private Collection $userCom;

    /**
     * @var Collection<int, JournalEmotionnel>
     */
    #[ORM\OneToMany(targetEntity: JournalEmotionnel::class, mappedBy: 'utilisateur')]
    private Collection $userjour;

    /**
     * @var Collection<int, TendanceEmotionnelle>
     */
    #[ORM\OneToMany(targetEntity: TendanceEmotionnelle::class, mappedBy: 'utilisateur')]
    private Collection $usertend;

    public function __construct()
    {
         $this->rendezVousClients = new ArrayCollection();
         $this->rendezVousProfessionnels = new ArrayCollection();
        $this->utiliaccomp = new ArrayCollection();
        $this->pubuser = new ArrayCollection();
        $this->userCom = new ArrayCollection();
        $this->userjour = new ArrayCollection();
        $this->usertend = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getMotDePasse(): ?string
    {
        return $this->motDePasse;
    }

    public function setMotDePasse(string $motDePasse): static
    {
        $this->motDePasse = $motDePasse;

        return $this;
    }

   public function getRole(): RoleUtilisateur
    {
        return $this->role;
    }

    public function setRole(RoleUtilisateur $role): static
    {
        $this->role = $role;
        return $this;
    }


    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

   public function getStatut(): StatutUtilisateur
    {
        return $this->statut;
    }

    public function setStatut(StatutUtilisateur $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getDateCreation(): ?\DateTime
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTime $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    /**
     * @return Collection<int, RendezVous>
     */
    public function getRendezVousClients(): Collection
    {
        return $this->rendezVousClients;
    }

    public function addRendezVousClients(RendezVous $idUtilisateur): static
    {
        if (!$this->rendezVousClients->contains($idUtilisateur)) {
            $this->rendezVousClients->add($idUtilisateur);
            $idUtilisateur->setUtilisateur($this);
        }

        return $this;
    }

    public function removeRendezVousClients(RendezVous $idUtilisateur): static
    {
        if ($this->rendezVousClients->removeElement($idUtilisateur)) {
            // set the owning side to null (unless already changed)
            if ($idUtilisateur->getUtilisateur() === $this) {
                $idUtilisateur->setUtilisateur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, RendezVous>
     */
    public function getRendezVousProfessionnels(): Collection
    {
        return $this->rendezVousProfessionnels;
    }

    public function addRendezVousProfessionnels(RendezVous $idProfessionnel): static
    {
        if (!$this->rendezVousProfessionnels->contains($idProfessionnel)) {
            $this->rendezVousProfessionnels->add($idProfessionnel);
            $idProfessionnel->setProfessionnel($this);
        }

        return $this;
    }

    public function removeRendezVousProfessionnels(RendezVous $idProfessionnel): static
    {
        if ($this->rendezVousProfessionnels->removeElement($idProfessionnel)) {
            // set the owning side to null (unless already changed)
            if ($idProfessionnel->getProfessionnel() === $this) {
                $idProfessionnel->setProfessionnel(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Accompagnement>
     */
    public function getUtiliaccomp(): Collection
    {
        return $this->utiliaccomp;
    }

    public function addUtiliaccomp(Accompagnement $utiliaccomp): static
    {
        if (!$this->utiliaccomp->contains($utiliaccomp)) {
            $this->utiliaccomp->add($utiliaccomp);
            $utiliaccomp->setUtilisateur($this);
        }

        return $this;
    }

    public function removeUtiliaccomp(Accompagnement $utiliaccomp): static
    {
        if ($this->utiliaccomp->removeElement($utiliaccomp)) {
            // set the owning side to null (unless already changed)
            if ($utiliaccomp->getUtilisateur() === $this) {
                $utiliaccomp->setUtilisateur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Publication>
     */
    public function getPubuser(): Collection
    {
        return $this->pubuser;
    }

    public function addPubuser(Publication $pubuser): static
    {
        if (!$this->pubuser->contains($pubuser)) {
            $this->pubuser->add($pubuser);
            $pubuser->setUser($this);
        }

        return $this;
    }

    public function removePubuser(Publication $pubuser): static
    {
        if ($this->pubuser->removeElement($pubuser)) {
            // set the owning side to null (unless already changed)
            if ($pubuser->getUser() === $this) {
                $pubuser->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Commentaire>
     */
    public function getUserCom(): Collection
    {
        return $this->userCom;
    }

    public function addUserCom(Commentaire $userCom): static
    {
        if (!$this->userCom->contains($userCom)) {
            $this->userCom->add($userCom);
            $userCom->setUser($this);
        }

        return $this;
    }

    public function removeUserCom(Commentaire $userCom): static
    {
        if ($this->userCom->removeElement($userCom)) {
            // set the owning side to null (unless already changed)
            if ($userCom->getUser() === $this) {
                $userCom->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, JournalEmotionnel>
     */
    public function getUserjour(): Collection
    {
        return $this->userjour;
    }

    public function addUserjour(JournalEmotionnel $userjour): static
    {
        if (!$this->userjour->contains($userjour)) {
            $this->userjour->add($userjour);
            $userjour->setUtilisateur($this);
        }

        return $this;
    }

    public function removeUserjour(JournalEmotionnel $userjour): static
    {
        if ($this->userjour->removeElement($userjour)) {
            // set the owning side to null (unless already changed)
            if ($userjour->getUtilisateur() === $this) {
                $userjour->setUtilisateur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TendanceEmotionnelle>
     */
    public function getUsertend(): Collection
    {
        return $this->usertend;
    }

    public function addUsertend(TendanceEmotionnelle $usertend): static
    {
        if (!$this->usertend->contains($usertend)) {
            $this->usertend->add($usertend);
            $usertend->setUtilisateur($this);
        }

        return $this;
    }

    public function removeUsertend(TendanceEmotionnelle $usertend): static
    {
        if ($this->usertend->removeElement($usertend)) {
            // set the owning side to null (unless already changed)
            if ($usertend->getUtilisateur() === $this) {
                $usertend->setUtilisateur(null);
            }
        }

        return $this;
    }
     public function getUserIdentifier(): string
    {
        return $this->email;
    }

    
   
       public function getRoles(): array
{
    return match ($this->role) {
        RoleUtilisateur::ADMIN => ['ROLE_ADMIN'],
        RoleUtilisateur::PROFESSIONNEL => ['ROLE_PROFESSIONNEL'],
        RoleUtilisateur::CLIENT => ['ROLE_USER'],
    };


    }

    public function getPassword(): string
    {
        return $this->motDePasse;
    }
    public function getSalt(): ?string
{
    return null;
}

    public function eraseCredentials(): void
    {
        // Clear temporary sensitive data if needed
    }
}
