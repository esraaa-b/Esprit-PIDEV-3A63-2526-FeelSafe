<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\JournalEmotionnel;
use App\Entity\Publication;
use App\Entity\Commentaire;
use App\Entity\ActiviteBienEtre;
use App\Entity\SessionActivite;
use App\Entity\TendanceEmotionnelle;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\Table(name: 'utilisateur')]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé')]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'json')]
    private array $role = [];

    /**
     * ✅ La colonne s'appelle "mot_de_passe" dans la base de données
     * mais la propriété PHP reste $motDePasse
     */
    #[ORM\Column(name: 'mot_de_passe', type: 'string')]
    private ?string $motDePasse = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    private ?string $prenom = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 20)]
    private ?string $statut = 'actif';

    #[ORM\Column]
    private ?\DateTimeImmutable $dateCreation = null;

    #[ORM\OneToOne(mappedBy: 'utilisateur', cascade: ['persist', 'remove'])]
    private ?ConfidentialiteUtilisateur $confidentialite = null;
    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: JournalEmotionnel::class, orphanRemoval: true)]
    private Collection $journaux;
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Publication::class)]
    private Collection $publications;
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Commentaire::class)]
    private Collection $userCom;
    #[ORM\OneToMany(mappedBy: 'creePar', targetEntity: ActiviteBienEtre::class)]
    private Collection $activitesCrees;
    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: SessionActivite::class)]
    private Collection $sessionsActivites;
    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: TendanceEmotionnelle::class)]
    private Collection $usertend;


    public function __construct()
    {
        $this->dateCreation = new \DateTimeImmutable();
        $this->role = ['ROLE_CLIENT']; // Rôle par défaut
        $this->journaux = new ArrayCollection();
        $this->publications = new ArrayCollection();
        $this->userCom = new ArrayCollection();
        $this->activitesCrees = new ArrayCollection();
        $this->sessionsActivites = new ArrayCollection();
        $this->usertend = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->role;
        // Garantir que tout utilisateur a au moins ROLE_USER
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->role = $roles;
        return $this;
    }

    /**
     * Méthode requise par PasswordAuthenticatedUserInterface
     * Retourne la valeur de la colonne "mot_de_passe"
     */
    public function getPassword(): string
    {
        return $this->motDePasse;
    }

    /**
     * ✅ Méthode setPassword() requise par Symfony Security
     * Modifie la colonne "mot_de_passe" en base de données
     */
    public function setPassword(string $password): static
    {
        $this->motDePasse = $password;
        return $this;
    }

    /**
     * Méthode originale (conservée pour compatibilité avec votre code)
     * Modifie également la colonne "mot_de_passe"
     */
    public function setMotDePasse(string $motDePasse): static
    {
        $this->motDePasse = $motDePasse;
        return $this;
    }

    /**
     * Getter alternatif (optionnel, pour votre code)
     */
    public function getMotDePasse(): ?string
    {
        return $this->motDePasse;
    }

    public function eraseCredentials(): void
    {
        // Si vous stockez des données sensibles temporaires, nettoyez-les ici
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

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeImmutable $dateCreation): static
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getConfidentialite(): ?ConfidentialiteUtilisateur
    {
        return $this->confidentialite;
    }

    public function setConfidentialite(?ConfidentialiteUtilisateur $confidentialite): static
    {
        if ($this->confidentialite !== $confidentialite) {
            $this->confidentialite = $confidentialite;
            
            if ($confidentialite !== null && $confidentialite->getUtilisateur() !== $this) {
                $confidentialite->setUtilisateur($this);
            }
        }

        return $this;
    }

    public function getFullName(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles());
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('ROLE_ADMIN');
    }

    public function isProfessionnel(): bool
    {
        return $this->hasRole('ROLE_PROFESSIONNEL');
    }

    public function isClient(): bool
    {
        return $this->hasRole('ROLE_CLIENT');
    }
    /**
 * @return Collection<int, JournalEmotionnel>
 */
public function getJournaux(): Collection
{
    return $this->journaux;
}
public function addJournal(JournalEmotionnel $journal): static
{
    if (!$this->journaux->contains($journal)) {
        $this->journaux->add($journal);
        $journal->setUtilisateur($this);
    }

    return $this;
}

public function removeJournal(JournalEmotionnel $journal): static
{
    if ($this->journaux->removeElement($journal)) {
        if ($journal->getUtilisateur() === $this) {
            $journal->setUtilisateur(null);
        }
    }

    return $this;
}

public function getPublications(): Collection
{
    return $this->publications;
}

public function addPublication(Publication $publication): static
{
    if (!$this->publications->contains($publication)) {
        $this->publications->add($publication);
        $publication->setUser($this);
    }
    return $this;
}

public function removePublication(Publication $publication): static
{
    if ($this->publications->removeElement($publication)) {
        if ($publication->getUser() === $this) {
            $publication->setUser(null);
        }
    }
    return $this;
}

public function getUserCom(): Collection
{
    return $this->userCom;
}

public function addUserCom(Commentaire $commentaire): static
{
    if (!$this->userCom->contains($commentaire)) {
        $this->userCom->add($commentaire);
        $commentaire->setUser($this);
    }
    return $this;
}

public function removeUserCom(Commentaire $commentaire): static
{
    if ($this->userCom->removeElement($commentaire)) {
        if ($commentaire->getUser() === $this) {
            $commentaire->setUser(null);
        }
    }
    return $this;
}

public function getActivitesCrees(): Collection
{
    return $this->activitesCrees;
}

public function addActivitesCree(ActiviteBienEtre $activite): static
{
    if (!$this->activitesCrees->contains($activite)) {
        $this->activitesCrees->add($activite);
        $activite->setCreePar($this);
    }
    return $this;
}

public function removeActivitesCree(ActiviteBienEtre $activite): static
{
    if ($this->activitesCrees->removeElement($activite)) {
        if ($activite->getCreePar() === $this) {
            $activite->setCreePar(null);
        }
    }
    return $this;
}

public function getSessionsActivites(): Collection
{
    return $this->sessionsActivites;
}

public function addSessionsActivite(SessionActivite $session): static
{
    if (!$this->sessionsActivites->contains($session)) {
        $this->sessionsActivites->add($session);
        $session->setUtilisateur($this);
    }
    return $this;
}

public function removeSessionsActivite(SessionActivite $session): static
{
    if ($this->sessionsActivites->removeElement($session)) {
        if ($session->getUtilisateur() === $this) {
            $session->setUtilisateur(null);
        }
    }
    return $this;
}

public function getUsertend(): Collection
{
    return $this->usertend;
}

public function addUsertend(TendanceEmotionnelle $tendance): static
{
    if (!$this->usertend->contains($tendance)) {
        $this->usertend->add($tendance);
        $tendance->setUtilisateur($this);
    }
    return $this;
}

public function removeUsertend(TendanceEmotionnelle $tendance): static
{
    if ($this->usertend->removeElement($tendance)) {
        if ($tendance->getUtilisateur() === $this) {
            $tendance->setUtilisateur(null);
        }
    }
    return $this;
}

}
