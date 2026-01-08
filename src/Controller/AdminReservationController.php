<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/reservations')]
#[IsGranted('ROLE_ADMIN')]
class AdminReservationController extends AbstractController
{
    #[Route('/', name: 'app_admin_reservations_index', methods: ['GET'])]
    public function index(ReservationRepository $reservationRepository): Response
    {
        $reservations = $reservationRepository->findBy([], ['DateRes' => 'DESC']);

        return $this->render('administrateur/reservations/index.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_reservations_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Reservation $reservation): Response
    {
        return $this->render('administrateur/reservations/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/valider', name: 'app_admin_reservations_valider', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function valider(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('valider'.$reservation->getId(), $request->getPayload()->getString('_token'))) {
            $reservation->setStatut('confirmé');
            $entityManager->flush();

            $this->addFlash('success', 'Réservation validée avec succès !');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_admin_reservations_show', ['id' => $reservation->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/annuler', name: 'app_admin_reservations_annuler', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function annuler(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('annuler'.$reservation->getId(), $request->getPayload()->getString('_token'))) {
            // Remettre les places disponibles dans le vol
            if ($reservation->getVol()) {
                $nbPassagers = count($reservation->getPassagers());
                if ($nbPassagers === 0) {
                    // Si aucun passager, utiliser 1 comme valeur par défaut
                    $nbPassagers = 1;
                }

                $vol = $reservation->getVol();
                $vol->setPlacesDisponibles($vol->getPlacesDisponibles() + $nbPassagers);
            }

            $reservation->setStatut('annulé');
            $entityManager->flush();

            $this->addFlash('success', 'Réservation annulée avec succès !');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_admin_reservations_show', ['id' => $reservation->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/delete', name: 'app_admin_reservations_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reservation->getId(), $request->getPayload()->getString('_token'))) {
            try {
                // Remettre les places disponibles
                if ($reservation->getVol()) {
                    $nbPassagers = count($reservation->getPassagers());
                    if ($nbPassagers === 0) {
                        $nbPassagers = 1;
                    }

                    $vol = $reservation->getVol();
                    $vol->setPlacesDisponibles($vol->getPlacesDisponibles() + $nbPassagers);
                }

                $entityManager->remove($reservation);
                $entityManager->flush();

                $this->addFlash('success', 'Réservation supprimée avec succès !');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Impossible de supprimer cette réservation. Elle est peut-être liée à des paiements ou des tickets.');
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_admin_reservations_index', [], Response::HTTP_SEE_OTHER);
    }
}
