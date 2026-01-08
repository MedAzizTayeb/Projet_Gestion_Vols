<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $Reference = null;

    #[ORM\Column]
    private ?\DateTime $DateRes = null;

    // CORRECTION: La propriété doit être $Statut (pas $Satut)
    #[ORM\Column(length: 50)]
    private ?string $Statut = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    private ?Client $client = null;

    #[ORM\OneToOne(mappedBy: 'reservation', cascade: ['persist', 'remove'])]
    private ?Paiement $paiement = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    private ?Vol $vol = null;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(targetEntity: Passager::class, mappedBy: 'reservation', cascade: ['remove'])]
    private Collection $passagers;

    /**
     * @var Collection<int, Ticket>
     */
    #[ORM\OneToMany(targetEntity: Ticket::class, mappedBy: 'reservation')]
    private Collection $tickets;

    public function __construct()
    {
        $this->tickets = new ArrayCollection();
        $this->passagers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): ?string
    {
        return $this->Reference;
    }

    public function setReference(string $Reference): static
    {
        $this->Reference = $Reference;

        return $this;
    }

    public function getDateRes(): ?\DateTime
    {
        return $this->DateRes;
    }

    public function setDateRes(\DateTime $DateRes): static
    {
        $this->DateRes = $DateRes;

        return $this;
    }


    public function getStatut(): ?string
    {
        return $this->Statut;
    }

    public function setStatut(string $Statut): static
    {
        $this->Statut = $Statut;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function getPaiement(): ?Paiement
    {
        return $this->paiement;
    }

    public function setPaiement(?Paiement $paiement): static
    {
        // unset the owning side of the relation if necessary
        if ($paiement === null && $this->paiement !== null) {
            $this->paiement->setReservation(null);
        }

        // set the owning side of the relation if necessary
        if ($paiement !== null && $paiement->getReservation() !== $this) {
            $paiement->setReservation($this);
        }

        $this->paiement = $paiement;

        return $this;
    }

    public function getVol(): ?Vol
    {
        return $this->vol;
    }

    public function setVol(?Vol $vol): static
    {
        $this->vol = $vol;

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
            $ticket->setReservation($this);
        }

        return $this;
    }

    public function removeTicket(Ticket $ticket): static
    {
        if ($this->tickets->removeElement($ticket)) {
            // set the owning side to null (unless already changed)
            if ($ticket->getReservation() === $this) {
                $ticket->setReservation(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection
     */
    public function getPassagers(): Collection
    {
        return $this->passagers;
    }

    public function addPassager(Passager $passager): static
    {
        if (!$this->passagers->contains($passager)) {
            $this->passagers->add($passager);
            $passager->setReservation($this);
        }
        return $this;
    }

    public function removePassager(Passager $passager): static
    {
        if ($this->passagers->removeElement($passager)) {
            if ($passager->getReservation() === $this) {
                $passager->setReservation(null);
            }
        }
        return $this;
    }
}
