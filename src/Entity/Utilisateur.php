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
    private string $email;

    #[ORM\Column(type: 'json')]
    private array $role = [];

    #[ORM\Column(name: 'mot_de_passe', type: 'string', nullable: true)]
    private ?string $motDePasse = null;

    #[ORM\Column(length: 100)]
    private string $nom;

    #[ORM\Column(length: 100)]
    private string $prenom;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 20)]
    private string $statut = 'actif';

    #[ORM\Column]
    private \DateTimeImmutable $dateCreation;

    /**
     * Dernière connexion — mise à jour automatiquement à chaque login.
     * Utilisée pour calculer le statut actif/inactif automatiquement.
     */
    #[ORM\Column(name: 'last_login', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastLogin = null;

    #[ORM\Column(name: 'google_id', length: 255, nullable: true)]
    private ?string $googleId = null;

    #[ORM\Column(name: 'github_id', length: 255, nullable: true)]
    private ?string $githubId = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $avatar = null;

    #[ORM\OneToOne(mappedBy: 'utilisateur', cascade: ['persist', 'remove'])]
    private ?ConfidentialiteUtilisateur $confidentialite = null;

    #[ORM\OneToMany(
        mappedBy: 'utilisateur',
        targetEntity: JournalEmotionnel::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
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

    /* ═══════════════════════════════════════════
       CONSTRUCTOR
    ═══════════════════════════════════════════ */
    public function __construct()
    {
        $this->dateCreation      = new \DateTimeImmutable();
        $this->role              = ['ROLE_CLIENT'];
        $this->journaux          = new ArrayCollection();
        $this->publications      = new ArrayCollection();
        $this->userCom           = new ArrayCollection();
        $this->activitesCrees    = new ArrayCollection();
        $this->sessionsActivites = new ArrayCollection();
        $this->usertend          = new ArrayCollection();
    }

    /* ═══════════════════════════════════════════
       ID
    ═══════════════════════════════════════════ */
    public function getId(): ?int
    {
        return $this->id;
    }

    /* ═══════════════════════════════════════════
       EMAIL / USER IDENTIFIER
    ═══════════════════════════════════════════ */
    public function getEmail(): string
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

    /* ═══════════════════════════════════════════
       ROLES
    ═══════════════════════════════════════════ */
    public function getRoles(): array
    {
        $roles   = $this->role;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    /**
     * Alias utilisé dans AdminController (setRole)
     */
    public function setRole(array $roles): static
    {
        $this->role = $roles;
        return $this;
    }

    public function getRole(): array
    {
        return $this->role;
    }

    /**
     * Alias Symfony standard
     */
    public function setRoles(array $roles): static
    {
        $this->role = $roles;
        return $this;
    }

    /* ═══════════════════════════════════════════
       MOT DE PASSE
    ═══════════════════════════════════════════ */
    public function getPassword(): ?string
    {
        return $this->motDePasse;
    }

    public function setPassword(?string $password): static
    {
        $this->motDePasse = $password;
        return $this;
    }

    public function setMotDePasse(?string $motDePasse): static
    {
        $this->motDePasse = $motDePasse;
        return $this;
    }

    public function getMotDePasse(): ?string
    {
        return $this->motDePasse;
    }

    public function eraseCredentials(): void {}

    /* ═══════════════════════════════════════════
       INFORMATIONS PERSONNELLES
    ═══════════════════════════════════════════ */
    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): string
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

    public function getFullName(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }

    /* ═══════════════════════════════════════════
       STATUT (géré automatiquement)
    ═══════════════════════════════════════════ */
    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    /* ═══════════════════════════════════════════
       DATE CRÉATION
    ═══════════════════════════════════════════ */
    public function getDateCreation(): \DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeImmutable $dateCreation): static
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    /* ═══════════════════════════════════════════
       LAST LOGIN  ← NOUVEAU
       Mise à jour dans SecurityController à chaque connexion.
       Utilisé par AdminController pour calculer le statut.
    ═══════════════════════════════════════════ */
    public function getLastLogin(): ?\DateTimeInterface
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?\DateTimeInterface $lastLogin): static
    {
        $this->lastLogin = $lastLogin;
        return $this;
    }

    /**
     * Nombre de jours depuis la dernière connexion (ou depuis la création si jamais connecté).
     */
    public function getJoursInactivite(): int
    {
        $ref = $this->lastLogin ?? \DateTime::createFromImmutable($this->dateCreation);
        return (int) $ref->diff(new \DateTime())->days;
    }

    /**
     * Détermine si l'utilisateur est inactif selon le seuil donné (défaut 30 jours).
     */
    public function isInactif(int $seuilJours = 30): bool
    {
        return $this->getJoursInactivite() >= $seuilJours;
    }

    /* ═══════════════════════════════════════════
       OAUTH
    ═══════════════════════════════════════════ */
    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): static
    {
        $this->googleId = $googleId;
        return $this;
    }

    public function getGithubId(): ?string
    {
        return $this->githubId;
    }

    public function setGithubId(?string $githubId): static
    {
        $this->githubId = $githubId;
        return $this;
    }

    /* ═══════════════════════════════════════════
       AVATAR
    ═══════════════════════════════════════════ */
    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;
        return $this;
    }

    /* ═══════════════════════════════════════════
       HELPERS RÔLES
    ═══════════════════════════════════════════ */
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

    public function getRoleLabel(): string
    {
        if ($this->isAdmin())         return 'ADMIN';
        if ($this->isProfessionnel()) return 'PROFESSIONNEL';
        return 'CLIENT';
    }

    /* ═══════════════════════════════════════════
       CONFIDENTIALITÉ
    ═══════════════════════════════════════════ */
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

    /* ═══════════════════════════════════════════
       JOURNAUX ÉMOTIONNELS
    ═══════════════════════════════════════════ */
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
        $this->journaux->removeElement($journal);
        return $this;
    }

    /* ═══════════════════════════════════════════
       PUBLICATIONS
    ═══════════════════════════════════════════ */
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
        $this->publications->removeElement($publication);
        return $this;
    }

    /* ═══════════════════════════════════════════
       COMMENTAIRES
    ═══════════════════════════════════════════ */
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
        $this->userCom->removeElement($commentaire);
        return $this;
    }

    /* ═══════════════════════════════════════════
       ACTIVITÉS BIEN-ÊTRE
    ═══════════════════════════════════════════ */
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

    /* ═══════════════════════════════════════════
       SESSIONS ACTIVITÉS
    ═══════════════════════════════════════════ */
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
        $this->sessionsActivites->removeElement($session);
        return $this;
    }

    /* ═══════════════════════════════════════════
       TENDANCES ÉMOTIONNELLES
    ═══════════════════════════════════════════ */
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
        $this->usertend->removeElement($tendance);
        return $this;
    }
}