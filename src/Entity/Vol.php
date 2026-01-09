<?php

namespace App\Entity;

use App\Repository\VolRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VolRepository::class)]
class Vol
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $num_vol = null;

    #[ORM\Column]
    private ?\DateTime $DateDepart = null;

    #[ORM\Column]
    private ?\DateTime $DateArrive = null;

    #[ORM\Column(length: 255)]
    private ?string $port = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $escale = null;

    #[ORM\Column]
    private ?int $placesDisponibles = null;

    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'vol')]
    private Collection $reservations;

    #[ORM\ManyToOne(inversedBy: 'vols')]
    private ?Aeroport $depart = null;

    #[ORM\ManyToOne]
    private ?Aeroport $arrivee = null;

    #[ORM\ManyToOne(inversedBy: 'vols')]
    private ?Avion $avion = null;

    /**
     * @var Collection<int, Ticket>
     */
    #[ORM\OneToMany(targetEntity: Ticket::class, mappedBy: 'vol')]
    private Collection $tickets;

    #[ORM\ManyToOne(inversedBy: 'volsCrees')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Administrateur $creePar = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $statut = null;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
        $this->tickets = new ArrayCollection();
        $this->statut = 'planifié';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumVol(): ?string
    {
        return $this->num_vol;
    }

    public function setNumVol(string $num_vol): static
    {
        $this->num_vol = $num_vol;
        return $this;
    }

    public function getDateDepart(): ?\DateTime
    {
        return $this->DateDepart;
    }

    public function setDateDepart(\DateTime $DateDepart): static
    {
        $this->DateDepart = $DateDepart;
        return $this;
    }

    public function getDateArrive(): ?\DateTime
    {
        return $this->DateArrive;
    }

    public function setDateArrive(\DateTime $DateArrive): static
    {
        $this->DateArrive = $DateArrive;
        return $this;
    }

    public function getPort(): ?string
    {
        return $this->port;
    }

    public function setPort(string $port): static
    {
        $this->port = $port;
        return $this;
    }

    public function getEscale(): ?string
    {
        return $this->escale;
    }

    public function setEscale(?string $escale): static
    {
        $this->escale = $escale;
        return $this;
    }

    public function getPlacesDisponibles(): ?int
    {
        return $this->placesDisponibles;
    }

    public function setPlacesDisponibles(int $placesDisponibles): static
    {
        $this->placesDisponibles = $placesDisponibles;
        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setVol($this);
        }
        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            if ($reservation->getVol() === $this) {
                $reservation->setVol(null);
            }
        }
        return $this;
    }

    public function getDepart(): ?Aeroport
    {
        return $this->depart;
    }

    public function setDepart(?Aeroport $depart): static
    {
        $this->depart = $depart;
        return $this;
    }

    public function getArrivee(): ?Aeroport
    {
        return $this->arrivee;
    }

    public function setArrivee(?Aeroport $arrivee): static
    {
        $this->arrivee = $arrivee;
        return $this;
    }

    public function getAvion(): ?Avion
    {
        return $this->avion;
    }

    public function setAvion(?Avion $avion): static
    {
        $this->avion = $avion;
        return $this;
    }

    /**
     * @return Collection<int, Ticket>
     */
    public function getTickets(): Collection
    {
        return $this->tickets;
    }

    public function addTicket(Ticket $ticket): static
    {
        if (!$this->tickets->contains($ticket)) {
            $this->tickets->add($ticket);
            $ticket->setVol($this);
        }
        return $this;
    }

    public function removeTicket(Ticket $ticket): static
    {
        if ($this->tickets->removeElement($ticket)) {
            if ($ticket->getVol() === $this) {
                $ticket->setVol(null);
            }
        }
        return $this;
    }

    public function getCreePar(): ?Administrateur
    {
        return $this->creePar;
    }

    public function setCreePar(?Administrateur $creePar): static
    {
        $this->creePar = $creePar;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function __toString(): string
    {
        return $this->num_vol . ' - ' . $this->getDepart()?->getVille() . ' → ' . $this->getArrivee()?->getVille();
    }
}
