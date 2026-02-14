<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

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

    #[ORM\Column]
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

    public function __construct()
    {
        $this->dateCreation = new \DateTimeImmutable();
        $this->role = ['ROLE_CLIENT']; // Rôle par défaut
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
}