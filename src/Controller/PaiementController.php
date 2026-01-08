<?php

namespace App\Controller;

use App\Entity\Paiement;
use App\Entity\Ticket;
use App\Repository\ReservationRepository;
use App\Repository\PaiementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/paiement')]
#[IsGranted('ROLE_USER')]
class PaiementController extends AbstractController
{
    #[Route('/nouveau/{reservationId}', name: 'app_paiement_new', requirements: ['reservationId' => '\d+'], methods: ['GET', 'POST'])]
    public function new(
        int $reservationId,
        Request $request,
        ReservationRepository $reservationRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $reservation = $reservationRepository->find($reservationId);

        if (!$reservation) {
            $this->addFlash('error', 'Réservation introuvable');
            return $this->redirectToRoute('app_client_dashboard');
        }

        // Vérifier que la réservation appartient au client connecté
        $user = $this->getUser();
        if ($reservation->getClient() !== $user->getClient()) {
            $this->addFlash('error', 'Accès non autorisé');
            return $this->redirectToRoute('app_client_dashboard');
        }

        // Vérifier si paiement déjà effectué
        if ($reservation->getPaiement()) {
            $this->addFlash('warning', 'Cette réservation a déjà été payée');
            return $this->redirectToRoute('app_reservation_show', ['id' => $reservation->getId()]);
        }

        // FIXED: Get passenger count from actual data, not session
        $nbPassagers = $request->getSession()->get('reservation_nb_passagers_' . $reservation->getId(), 1);

        // SECURITY: Verify passenger count matches actual passengers if any exist
        $actualPassengers = count($reservation->getPassagers());
        if ($actualPassengers > 0) {
            $nbPassagers = $actualPassengers; // Use actual count
        }

        $prixParBillet = 150.00; // Prix par défaut - peut être configuré
        $montantTotal = $prixParBillet * $nbPassagers;

        if ($request->isMethod('POST')) {
            // Start transaction for data integrity
            $entityManager->beginTransaction();

            try {
                $method = $request->request->get('method', 'Carte Bancaire');

                // Validation de la méthode de paiement
                $methodesValides = ['Carte Bancaire', 'PayPal', 'Virement Bancaire', 'Paiement à l\'Aéroport'];
                if (!in_array($method, $methodesValides)) {
                    throw new \Exception('Méthode de paiement invalide');
                }

                // Créer le paiement
                $paiement = new Paiement();
                $paiement->setMontant(number_format($montantTotal, 2, '.', ''));
                $paiement->setMethod($method);
                $paiement->setSatut('confirmé');
                $paiement->setReservation($reservation);

                $entityManager->persist($paiement);

                // Mettre à jour le statut de la réservation
                $reservation->setSatut('confirmé');

                // Générer les tickets automatiquement
                for ($i = 1; $i <= $nbPassagers; $i++) {
                    $ticket = new Ticket();
                    $ticket->setIdTicket(random_int(100000, 999999));
                    $ticket->setNumero('TKT-' . strtoupper(uniqid()));
                    $ticket->setDateCreation(new \DateTime());
                    $ticket->setPdfPath('/tickets/' . $ticket->getNumero() . '.pdf');
                    $ticket->setReservation($reservation);
                    $ticket->setVol($reservation->getVol());

                    $entityManager->persist($ticket);
                }

                $entityManager->flush();
                $entityManager->commit(); // Commit transaction

                // Nettoyer la session
                $request->getSession()->remove('reservation_nb_passagers_' . $reservation->getId());

                $this->addFlash('success', 'Paiement confirmé ! Vos billets ont été générés.');

                return $this->redirectToRoute('app_paiement_success', ['id' => $paiement->getId()]);

            } catch (\Exception $e) {
                $entityManager->rollback(); // Rollback on error
                $this->addFlash('error', 'Erreur lors du paiement : ' . $e->getMessage());
            }
        }

        return $this->render('paiement/new.html.twig', [
            'reservation' => $reservation,
            'nbPassagers' => $nbPassagers,
            'prixParBillet' => $prixParBillet,
            'montantTotal' => $montantTotal,
        ]);
    }

    #[Route('/succes/{id}', name: 'app_paiement_success', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function success(Paiement $paiement): Response
    {
        // Vérifier que le paiement appartient au client connecté
        $user = $this->getUser();
        if ($paiement->getReservation()->getClient() !== $user->getClient()) {
            $this->addFlash('error', 'Accès non autorisé');
            return $this->redirectToRoute('app_client_dashboard');
        }

        return $this->render('paiement/success.html.twig', [
            'paiement' => $paiement,
            'reservation' => $paiement->getReservation(),
        ]);
    }

    #[Route('/admin', name: 'app_paiement_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(PaiementRepository $paiementRepository): Response
    {
        $paiements = $paiementRepository->findBy([], ['id' => 'DESC']);

        return $this->render('administrateur/paiement/index.html.twig', [
            'paiements' => $paiements,
        ]);
    }

    #[Route('/admin/{id}', name: 'app_paiement_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function show(Paiement $paiement): Response
    {
        return $this->render('administrateur/paiement/show.html.twig', [
            'paiement' => $paiement,
        ]);
    }

    #[Route('/admin/{id}/rembourser', name: 'app_paiement_refund', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function refund(
        Paiement $paiement,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('refund' . $paiement->getId(), $request->request->get('_token'))) {
            try {
                // Marquer le paiement comme remboursé
                $paiement->setStatut('remboursé');

                // Annuler la réservation associée
                $reservation = $paiement->getReservation();
                $reservation->setSatut('annulé');

                // Remettre les places disponibles
                $vol = $reservation->getVol();
                $nbPassagers = $request->getSession()->get('reservation_nb_passagers_' . $reservation->getId(), 1);
                $vol->setPlacesDisponibles($vol->getPlacesDisponibles() + $nbPassagers);

                $entityManager->flush();

                $this->addFlash('success', 'Paiement remboursé avec succès');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors du remboursement : ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_paiement_index');
    }
}
