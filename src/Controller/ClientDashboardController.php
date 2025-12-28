<?php

namespace App\Controller;

use App\Repository\ReservationRepository;
use App\Repository\TicketRepository;
use App\Repository\VolRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/client')]
#[IsGranted('ROLE_USER')]
class ClientDashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_client_dashboard')]
    public function dashboard(
        ReservationRepository $reservationRepository,
        TicketRepository $ticketRepository,
        VolRepository $volRepository
    ): Response {
        $user = $this->getUser();
        $client = $user->getClient();

        if (!$client) {
            $this->addFlash('error', 'Profil client non trouvé.');
            return $this->redirectToRoute('app_home');
        }

        // Récupérer les réservations du client
        $reservations = $reservationRepository->findBy(
            ['client' => $client],
            ['DateRes' => 'DESC']
        );

        // Récupérer les tickets du client via les réservations
        $tickets = [];
        foreach ($reservations as $reservation) {
            foreach ($reservation->getTickets() as $ticket) {
                $tickets[] = $ticket;
            }
        }

        // Vols disponibles
        $volsDisponibles = $volRepository->createQueryBuilder('v')
            ->where('v.DateDepart > :now')
            ->andWhere('v.placesDisponibles > 0')
            ->setParameter('now', new \DateTime())
            ->orderBy('v.DateDepart', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $this->render('client/dashboard.html.twig', [
            'client' => $client,
            'reservations' => $reservations,
            'tickets' => $tickets,
            'vols_disponibles' => $volsDisponibles,
        ]);
    }

    #[Route('/profil', name: 'app_client_profil')]
    public function profil(): Response
    {
        $user = $this->getUser();
        $client = $user->getClient();

        if (!$client) {
            $this->addFlash('error', 'Profil client non trouvé.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('client/profil.html.twig', [
            'client' => $client,
        ]);
    }

    #[Route('/mes-reservations', name: 'app_client_reservations')]
    public function reservations(ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        $client = $user->getClient();

        if (!$client) {
            $this->addFlash('error', 'Profil client non trouvé.');
            return $this->redirectToRoute('app_home');
        }

        $reservations = $reservationRepository->findBy(
            ['client' => $client],
            ['DateRes' => 'DESC']
        );

        return $this->render('client/reservations.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    #[Route('/mes-tickets', name: 'app_client_tickets')]
    public function tickets(ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        $client = $user->getClient();

        if (!$client) {
            $this->addFlash('error', 'Profil client non trouvé.');
            return $this->redirectToRoute('app_home');
        }

        $reservations = $reservationRepository->findBy(['client' => $client]);

        $tickets = [];
        foreach ($reservations as $reservation) {
            foreach ($reservation->getTickets() as $ticket) {
                $tickets[] = $ticket;
            }
        }

        return $this->render('client/tickets.html.twig', [
            'tickets' => $tickets,
        ]);
    }
}
