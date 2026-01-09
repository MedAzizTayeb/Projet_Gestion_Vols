<?php

namespace App\Controller;

use App\Repository\ClientRepository;
use App\Repository\ReservationRepository;
use App\Repository\TicketRepository;
use App\Repository\VolRepository;
use App\Repository\PaiementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/rapports')]
#[IsGranted('ROLE_ADMIN')]
class AdminRapportsController extends AbstractController
{
    #[Route('/', name: 'app_admin_rapports_index', methods: ['GET'])]
    public function index(
        VolRepository $volRepository,
        ReservationRepository $reservationRepository,
        TicketRepository $ticketRepository,
        ClientRepository $clientRepository,
        PaiementRepository $paiementRepository
    ): Response {
        $today = new \DateTime('today');
        $startOfMonth = new \DateTime('first day of this month');
        $startOfYear = new \DateTime('first day of January this year');

        // Vols aujourd'hui
        $endOfDay = new \DateTime('tomorrow');
        $volsAujourdhui = $volRepository->createQueryBuilder('v')
            ->where('v.DateDepart >= :today')
            ->andWhere('v.DateDepart < :endOfDay')
            ->setParameter('today', $today)
            ->setParameter('endOfDay', $endOfDay)
            ->getQuery()
            ->getResult();

        // Réservations ce mois
        $reservationsMois = $reservationRepository->createQueryBuilder('r')
            ->where('r.DateRes >= :startOfMonth')
            ->setParameter('startOfMonth', $startOfMonth)
            ->getQuery()
            ->getResult();

        // Tickets cette année
        $ticketsAnnee = $ticketRepository->createQueryBuilder('t')
            ->where('t.dateCreation >= :startOfYear')
            ->setParameter('startOfYear', $startOfYear)
            ->getQuery()
            ->getResult();

        // Clients actifs (ayant au moins une réservation)
        $clientsActifs = $clientRepository->createQueryBuilder('c')
            ->leftJoin('c.reservations', 'r')
            ->where('r.id IS NOT NULL')
            ->groupBy('c.id')
            ->getQuery()
            ->getResult();

        // Revenus ce mois (via la date de réservation)
        $revenusMois = $paiementRepository->createQueryBuilder('p')
            ->select('SUM(p.montant) as total')
            ->leftJoin('p.reservation', 'r')
            ->where('r.DateRes >= :startOfMonth')
            ->andWhere('p.Statut = :statut')
            ->setParameter('startOfMonth', $startOfMonth)
            ->setParameter('statut', 'validé')
            ->getQuery()
            ->getSingleScalarResult();

        // Revenus cette année (via la date de réservation)
        $revenusAnnee = $paiementRepository->createQueryBuilder('p')
            ->select('SUM(p.montant) as total')
            ->leftJoin('p.reservation', 'r')
            ->where('r.DateRes >= :startOfYear')
            ->andWhere('p.Statut = :statut')
            ->setParameter('startOfYear', $startOfYear)
            ->setParameter('statut', 'validé')
            ->getQuery()
            ->getSingleScalarResult();

        // Statistiques générales
        $totalVols = $volRepository->count([]);
        $totalReservations = $reservationRepository->count([]);
        $totalClients = $clientRepository->count([]);
        $totalTickets = $ticketRepository->count([]);

        // Réservations par statut
        $reservationsParStatut = $reservationRepository->createQueryBuilder('r')
            ->select('r.Statut, COUNT(r.id) as nombre')
            ->groupBy('r.Statut')
            ->getQuery()
            ->getResult();

        // Vols les plus réservés
        $volsPopulaires = $volRepository->createQueryBuilder('v')
            ->select('v, COUNT(r.id) as nbReservations')
            ->leftJoin('v.reservations', 'r')
            ->groupBy('v.id')
            ->orderBy('nbReservations', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Dernières réservations
        $dernieresReservations = $reservationRepository->findBy(
            [],
            ['DateRes' => 'DESC'],
            10
        );

        // Taux d'occupation moyen
        $tauxOccupation = $this->calculerTauxOccupation($volRepository);

        return $this->render('administrateur/rapports/index.html.twig', [
            'rapports' => [
                'vols_aujourdhui' => count($volsAujourdhui),
                'reservations_mois' => count($reservationsMois),
                'tickets_annee' => count($ticketsAnnee),
                'clients_actifs' => count($clientsActifs),
                'revenus_mois' => $revenusMois ?? 0,
                'revenus_annee' => $revenusAnnee ?? 0,
                'total_vols' => $totalVols,
                'total_reservations' => $totalReservations,
                'total_clients' => $totalClients,
                'total_tickets' => $totalTickets,
                'reservations_par_statut' => $reservationsParStatut,
                'vols_populaires' => $volsPopulaires,
                'dernieres_reservations' => $dernieresReservations,
                'taux_occupation' => $tauxOccupation,
                'top_destinations' => [], // À implémenter si nécessaire
            ],
        ]);
    }

    private function calculerTauxOccupation(VolRepository $volRepository): float
    {
        $vols = $volRepository->findAll();

        if (empty($vols)) {
            return 0;
        }

        $totalCapacite = 0;
        $totalReserve = 0;

        foreach ($vols as $vol) {
            if ($vol->getAvion()) {
                $capacite = $vol->getAvion()->getCapacite();
                $disponibles = $vol->getPlacesDisponibles();
                $reserves = $capacite - $disponibles;

                $totalCapacite += $capacite;
                $totalReserve += $reserves;
            }
        }

        if ($totalCapacite === 0) {
            return 0;
        }

        return round(($totalReserve / $totalCapacite) * 100, 2);
    }
}
