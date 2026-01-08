<?php

namespace App\Controller;

use App\Entity\Paiement;
use App\Entity\Ticket;
use App\Repository\ReservationRepository;
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

        if ($request->isMethod('POST')) {
            try {
                // Créer le paiement
                $paiement = new Paiement();
                $paiement->setMontant($request->request->get('montant', '100.00'));
                $paiement->setMethod($request->request->get('method', 'Carte Bancaire'));
                $paiement->setStatut('confirmé');
                $paiement->setReservation($reservation);

                $entityManager->persist($paiement);

                // Mettre à jour le statut de la réservation
                $reservation->setSatut('confirmé');

                // Générer les tickets automatiquement
                $nbPassagers = $request->getSession()->get('reservation_nb_passagers_' . $reservation->getId(), 1);

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

                $this->addFlash('success', 'Paiement confirmé ! Vos billets ont été générés.');

                return $this->redirectToRoute('app_paiement_success', ['id' => $paiement->getId()]);

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors du paiement : ' . $e->getMessage());
            }
        }

        return $this->render('paiement/new.html.twig', [
            'reservation' => $reservation,
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
    public function index(EntityManagerInterface $entityManager): Response
    {
        $paiements = $entityManager->getRepository(Paiement::class)->findAll();

        return $this->render('paiement/index.html.twig', [
            'paiements' => $paiements,
        ]);
    }

    #[Route('/admin/{id}', name: 'app_paiement_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function show(Paiement $paiement): Response
    {
        return $this->render('paiement/show.html.twig', [
            'paiement' => $paiement,
        ]);
    }
}
