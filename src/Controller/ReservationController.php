<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Repository\VolRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/reservation')]
class ReservationController extends AbstractController
{
    #[Route('/creer/{volId}', name: 'app_reservation_create', requirements: ['volId' => '\d+'], methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(
        int $volId,
        Request $request,
        VolRepository $volRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $vol = $volRepository->find($volId);

        if (!$vol) {
            $this->addFlash('error', 'Vol introuvable');
            return $this->redirectToRoute('app_vol_index');
        }

        if ($vol->getPlacesDisponibles() <= 0) {
            $this->addFlash('error', 'Aucune place disponible pour ce vol');
            return $this->redirectToRoute('app_vol_show', ['id' => $volId]);
        }

        $user = $this->getUser();
        $client = $user->getClient();

        if (!$client) {
            $this->addFlash('error', 'Profil client introuvable');
            return $this->redirectToRoute('app_client_dashboard');
        }

        if ($request->isMethod('POST')) {
            $nbPassagers = (int) $request->request->get('nb_passagers', 1);

            if ($nbPassagers > $vol->getPlacesDisponibles()) {
                $this->addFlash('error', 'Pas assez de places disponibles');
                return $this->redirectToRoute('app_reservation_create', ['volId' => $volId]);
            }

            if ($nbPassagers < 1 || $nbPassagers > 9) {
                $this->addFlash('error', 'Nombre de passagers invalide (1-9)');
                return $this->redirectToRoute('app_reservation_create', ['volId' => $volId]);
            }

            $reservation = new Reservation();
            $reservation->setReference('RES-' . strtoupper(uniqid()));
            $reservation->setDateRes(new \DateTime());
            $reservation->setSatut('en attente');
            $reservation->setClient($client);
            $reservation->setVol($vol);

            // Mettre à jour les places disponibles
            $vol->setPlacesDisponibles($vol->getPlacesDisponibles() - $nbPassagers);

            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash('success', 'Réservation créée avec succès ! Référence: ' . $reservation->getReference());

            // Stocker le nombre de passagers en session pour le paiement
            $request->getSession()->set('reservation_nb_passagers_' . $reservation->getId(), $nbPassagers);

            return $this->redirectToRoute('app_reservation_show', ['id' => $reservation->getId()]);
        }

        return $this->render('reservation/create.html.twig', [
            'vol' => $vol,
        ]);
    }

    #[Route('/{id}', name: 'app_reservation_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(Reservation $reservation): Response
    {
        // Vérifier que la réservation appartient au client connecté
        $user = $this->getUser();
        if ($reservation->getClient() !== $user->getClient()) {
            $this->addFlash('error', 'Vous n\'avez pas accès à cette réservation');
            return $this->redirectToRoute('app_client_dashboard');
        }

        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/mes-reservations', name: 'app_mes_reservations', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function mesReservations(ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        $client = $user->getClient();

        if (!$client) {
            $this->addFlash('error', 'Profil client introuvable');
            return $this->redirectToRoute('app_home');
        }

        $reservations = $reservationRepository->findBy(
            ['client' => $client],
            ['DateRes' => 'DESC']
        );

        return $this->render('reservation/mes_reservations.html.twig', [
            'reservations' => $reservations,
        ]);
    }
}
