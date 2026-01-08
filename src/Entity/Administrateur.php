<?php

namespace App\Entity;

use App\Repository\AdministrateurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: AdministrateurRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé')]
#[UniqueEntity(fields: ['matricule'], message: 'Ce matricule est déjà utilisé')]
class Administrateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $matricule = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private ?string $password = null;

    /**
     * @var array<string>
     */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?int $niveauAcces = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $dateCreation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $dernierConnexion = null;

    #[ORM\Column]
    private ?bool $actif = true;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $departement = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $permissions = null;

    /**
     * @var Collection<int, Vol>
     */
    #[ORM\OneToMany(targetEntity: Vol::class, mappedBy: 'creePar')]
    private Collection $volsCrees;

    /**
     * @var Collection<int, Avion>
     */
    #[ORM\OneToMany(targetEntity: Avion::class, mappedBy: 'gerePar')]
    private Collection $avionsGeres;

    public function __construct()
    {
        $this->volsCrees = new ArrayCollection();
        $this->avionsGeres = new ArrayCollection();
        $this->dateCreation = new \DateTime();
        $this->actif = true;
        $this->roles = ['ROLE_ADMIN'];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMatricule(): ?string
    {
        return $this->matricule;
    }

    public function setMatricule(string $matricule): static
    {
        $this->matricule = $matricule;
        return $this;
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

    /**
     * A visual identifier that represents this user.
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_ADMIN
        $roles[] = 'ROLE_ADMIN';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function getNiveauAcces(): ?int
    {
        return $this->niveauAcces;
    }

    public function setNiveauAcces(int $niveauAcces): static
    {
        $this->niveauAcces = $niveauAcces;
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

    public function getDateCreation(): ?\DateTime
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTime $dateCreation): static
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getDernierConnexion(): ?\DateTime
    {
        return $this->dernierConnexion;
    }

    public function setDernierConnexion(?\DateTime $dernierConnexion): static
    {
        $this->dernierConnexion = $dernierConnexion;
        return $this;
    }

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;
        return $this;
    }

    public function getDepartement(): ?string
    {
        return $this->departement;
    }

    public function setDepartement(?string $departement): static
    {
        $this->departement = $departement;
        return $this;
    }

    public function getPermissions(): ?string
    {
        return $this->permissions;
    }

    public function setPermissions(?string $permissions): static
    {
        $this->permissions = $permissions;
        return $this;
    }

    /**
     * @return Collection<int, Vol>
     */
    public function getVolsCrees(): Collection
    {
        return $this->volsCrees;
    }

    public function addVolsCree(Vol $volsCree): static
    {
        if (!$this->volsCrees->contains($volsCree)) {
            $this->volsCrees->add($volsCree);
            $volsCree->setCreePar($this);
        }
        return $this;
    }

    public function removeVolsCree(Vol $volsCree): static
    {
        if ($this->volsCrees->removeElement($volsCree)) {
            if ($volsCree->getCreePar() === $this) {
                $volsCree->setCreePar(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Avion>
     */
    public function getAvionsGeres(): Collection
    {
        return $this->avionsGeres;
    }

    public function addAvionsGere(Avion $avionsGere): static
    {
        if (!$this->avionsGeres->contains($avionsGere)) {
            $this->avionsGeres->add($avionsGere);
            $avionsGere->setGerePar($this);
        }
        return $this;
    }

    public function removeAvionsGere(Avion $avionsGere): static
    {
        if ($this->avionsGeres->removeElement($avionsGere)) {
            if ($avionsGere->getGerePar() === $this) {
                $avionsGere->setGerePar(null);
            }
        }
        return $this;
    }

    public function getNomComplet(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }

    public function __toString(): string
    {
        return $this->getNomComplet() . ' (' . $this->matricule . ')';
    }
}
