<?php

namespace App\Entity;

use App\Repository\PassagerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PassagerRepository::class)]
class Passager
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $numPassport = null;

    #[ORM\Column(length: 255)]
    private ?string $nationalite = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateNaissance = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $besoinsSpeciaux = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $poidsBagages = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?reservation $reservation = null;

    /**
     * @var Collection<int, Ticket>
     */
    #[ORM\OneToMany(targetEntity: Ticket::class, mappedBy: 'passager')]
    private Collection $tickets;

    public function __construct()
    {
        $this->tickets = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumPassport(): ?string
    {
        return $this->numPassport;
    }

    public function setNumPassport(string $numPassport): static
    {
        $this->numPassport = $numPassport;

        return $this;
    }

    public function getNationalite(): ?string
    {
        return $this->nationalite;
    }

    public function setNationalite(string $nationalite): static
    {
        $this->nationalite = $nationalite;

        return $this;
    }

    public function getDateNaissance(): ?\DateTime
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(\DateTime $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;

        return $this;
    }

    public function getBesoinsSpeciaux(): ?string
    {
        return $this->besoinsSpeciaux;
    }

    public function setBesoinsSpeciaux(?string $besoinsSpeciaux): static
    {
        $this->besoinsSpeciaux = $besoinsSpeciaux;

        return $this;
    }

    public function getPoidsBagages(): ?string
    {
        return $this->poidsBagages;
    }

    public function setPoidsBagages(string $poidsBagages): static
    {
        $this->poidsBagages = $poidsBagages;

        return $this;
    }

    public function getReservation(): ?reservation
    {
        return $this->reservation;
    }

    public function setReservation(?reservation $reservation): static
    {
        $this->reservation = $reservation;

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
            $ticket->setPassager($this);
        }

        return $this;
    }

    public function removeTicket(Ticket $ticket): static
    {
        if ($this->tickets->removeElement($ticket)) {
            // set the owning side to null (unless already changed)
            if ($ticket->getPassager() === $this) {
                $ticket->setPassager(null);
            }
        }

        return $this;
    }
}
