<?php

namespace App\Entity;

use App\Repository\AvionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AvionRepository::class)]
class Avion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $modele = null;

    #[ORM\Column]
    private ?int $capacite = null;

    #[ORM\Column]
    private ?bool $disponibilite = null;

    /**
     * @var Collection<int, Vol>
     */
    #[ORM\OneToMany(targetEntity: Vol::class, mappedBy: 'avion')]
    private Collection $vols;

    #[ORM\ManyToOne(inversedBy: 'avions')]
    private ?CategorieAvion $categorie = null;

    #[ORM\ManyToOne(inversedBy: 'avionsGeres')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Administrateur $gerePar = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $immatriculation = null;

    #[ORM\Column(nullable: true)]
    private ?int $heuresVol = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $derniereMaintenance = null;

    public function __construct()
    {
        $this->vols = new ArrayCollection();
        $this->disponibilite = true;
        $this->heuresVol = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getModele(): ?string
    {
        return $this->modele;
    }

    public function setModele(string $modele): static
    {
        $this->modele = $modele;
        return $this;
    }

    public function getCapacite(): ?int
    {
        return $this->capacite;
    }

    public function setCapacite(int $capacite): static
    {
        $this->capacite = $capacite;
        return $this;
    }

    public function isDisponibilite(): ?bool
    {
        return $this->disponibilite;
    }

    public function setDisponibilite(bool $disponibilite): static
    {
        $this->disponibilite = $disponibilite;
        return $this;
    }

    /**
     * @return Collection<int, Vol>
     */
    public function getVols(): Collection
    {
        return $this->vols;
    }

    public function addVol(Vol $vol): static
    {
        if (!$this->vols->contains($vol)) {
            $this->vols->add($vol);
            $vol->setAvion($this);
        }
        return $this;
    }

    public function removeVol(Vol $vol): static
    {
        if ($this->vols->removeElement($vol)) {
            if ($vol->getAvion() === $this) {
                $vol->setAvion(null);
            }
        }
        return $this;
    }

    public function getCategorie(): ?CategorieAvion
    {
        return $this->categorie;
    }

    public function setCategorie(?CategorieAvion $categorie): static
    {
        $this->categorie = $categorie;
        return $this;
    }

    public function getGerePar(): ?Administrateur
    {
        return $this->gerePar;
    }

    public function setGerePar(?Administrateur $gerePar): static
    {
        $this->gerePar = $gerePar;
        return $this;
    }

    public function getImmatriculation(): ?string
    {
        return $this->immatriculation;
    }

    public function setImmatriculation(?string $immatriculation): static
    {
        $this->immatriculation = $immatriculation;
        return $this;
    }

    public function getHeuresVol(): ?int
    {
        return $this->heuresVol;
    }

    public function setHeuresVol(?int $heuresVol): static
    {
        $this->heuresVol = $heuresVol;
        return $this;
    }

    public function getDerniereMaintenance(): ?\DateTime
    {
        return $this->derniereMaintenance;
    }

    public function setDerniereMaintenance(?\DateTime $derniereMaintenance): static
    {
        $this->derniereMaintenance = $derniereMaintenance;
        return $this;
    }

    public function __toString(): string
    {
        return $this->modele . ' (' . $this->immatriculation . ')';
    }
}
