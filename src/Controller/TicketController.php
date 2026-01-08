<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Form\TicketType;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/ticket')]
class TicketController extends AbstractController
{
    // Client routes
    #[Route('/', name: 'app_ticket_index', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(TicketRepository $ticketRepository): Response
    {
        $user = $this->getUser();

        // If admin, show all tickets
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_ticket_admin_index');
        }

        // For clients, show only their tickets
        $client = $user->getClient();

        if (!$client) {
            $this->addFlash('error', 'Profil client introuvable');
            return $this->redirectToRoute('app_client_dashboard');
        }

        // Get all tickets for this client's reservations
        $tickets = $ticketRepository->createQueryBuilder('t')
            ->join('t.reservation', 'r')
            ->where('r.client = :client')
            ->setParameter('client', $client)
            ->orderBy('t.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('ticket/index.html.twig', [
            'tickets' => $tickets,
        ]);
    }

    #[Route('/{id}', name: 'app_ticket_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(Ticket $ticket): Response
    {
        $user = $this->getUser();

        // If admin, redirect to admin view
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_ticket_admin_show', ['id' => $ticket->getId()]);
        }

        $client = $user->getClient();

        // Verify the ticket belongs to the current user
        if ($ticket->getReservation()->getClient() !== $client) {
            $this->addFlash('error', 'Accès non autorisé');
            return $this->redirectToRoute('app_ticket_index');
        }

        return $this->render('ticket/show.html.twig', [
            'ticket' => $ticket,
        ]);
    }

    #[Route('/{id}/telecharger', name: 'app_ticket_download', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function download(Ticket $ticket): Response
    {
        $user = $this->getUser();
        $client = $user->getClient();

        // Verify the ticket belongs to the current user
        if ($ticket->getReservation()->getClient() !== $client) {
            $this->addFlash('error', 'Accès non autorisé');
            return $this->redirectToRoute('app_ticket_index');
        }

        // For now, redirect to show page
        // In production, you would generate/serve the actual PDF here
        $this->addFlash('info', 'La génération du PDF sera implémentée prochainement');

        return $this->redirectToRoute('app_ticket_show', ['id' => $ticket->getId()]);
    }

    // Admin routes
    #[Route('/admin', name: 'app_ticket_admin_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminIndex(TicketRepository $ticketRepository): Response
    {
        $tickets = $ticketRepository->findBy([], ['dateCreation' => 'DESC']);

        return $this->render('administrateur/ticket/index.html.twig', [
            'tickets' => $tickets,
        ]);
    }

    #[Route('/admin/new', name: 'app_ticket_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $ticket = new Ticket();
        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Auto-generate ticket number if not set
            if (!$ticket->getNumero()) {
                $ticket->setNumero('TKT-' . strtoupper(uniqid()));
            }

            // Auto-generate idTicket if not set
            if (!$ticket->getIdTicket()) {
                $ticket->setIdTicket(random_int(100000, 999999));
            }

            // Set creation date if not set
            if (!$ticket->getDateCreation()) {
                $ticket->setDateCreation(new \DateTime());
            }

            // Set default PDF path if not set
            if (!$ticket->getPdfPath()) {
                $ticket->setPdfPath('/tickets/' . $ticket->getNumero() . '.pdf');
            }

            $entityManager->persist($ticket);
            $entityManager->flush();

            $this->addFlash('success', 'Billet créé avec succès !');

            return $this->redirectToRoute('app_ticket_admin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('administrateur/ticket/new.html.twig', [
            'ticket' => $ticket,
            'form' => $form,
        ]);
    }

    #[Route('/admin/{id}', name: 'app_ticket_admin_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminShow(Ticket $ticket): Response
    {
        return $this->render('administrateur/ticket/show.html.twig', [
            'ticket' => $ticket,
        ]);
    }

    #[Route('/admin/{id}/edit', name: 'app_ticket_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Ticket $ticket, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Billet modifié avec succès !');

            return $this->redirectToRoute('app_ticket_admin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('administrateur/ticket/edit.html.twig', [
            'ticket' => $ticket,
            'form' => $form,
        ]);
    }

    #[Route('/admin/{id}', name: 'app_ticket_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Ticket $ticket, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$ticket->getId(), $request->request->get('_token'))) {
            $entityManager->remove($ticket);
            $entityManager->flush();

            $this->addFlash('success', 'Billet supprimé avec succès !');
        }

        return $this->redirectToRoute('app_ticket_admin_index', [], Response::HTTP_SEE_OTHER);
    }
}
