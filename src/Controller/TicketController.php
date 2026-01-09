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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class TicketController extends AbstractController
{
    // Client routes
    #[Route('/client/tickets', name: 'app_client_tickets', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function clientIndex(TicketRepository $ticketRepository): Response
    {
        $user = $this->getUser();
        $client = $user->getClient();

        if (!$client) {
            $this->addFlash('error', 'Profil client introuvable');
            return $this->redirectToRoute('app_client_dashboard');
        }

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

    #[Route('/client/ticket/{id}', name: 'app_client_ticket_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function clientShow(Ticket $ticket): Response
    {
        $user = $this->getUser();
        $client = $user->getClient();

        if (!$client || $ticket->getReservation()->getClient() !== $client) {
            $this->addFlash('error', 'Accès non autorisé');
            return $this->redirectToRoute('app_client_tickets');
        }

        return $this->render('ticket/show.html.twig', [
            'ticket' => $ticket,
        ]);
    }

    #[Route('/client/ticket/{id}/telecharger', name: 'app_client_ticket_download', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function clientDownload(Ticket $ticket): Response
    {
        $user = $this->getUser();
        $client = $user->getClient();

        if (!$client || $ticket->getReservation()->getClient() !== $client) {
            $this->addFlash('error', 'Accès non autorisé');
            return $this->redirectToRoute('app_client_tickets');
        }

        // Render HTML
        $html = $this->renderView('ticket/pdf.html.twig', [
            'ticket' => $ticket,
        ]);

        // Try PDF generation if library exists
        if (class_exists('\Dompdf\Dompdf')) {
            try {
                $pdfOptions = new \Dompdf\Options();
                $pdfOptions->set('defaultFont', 'DejaVu Sans');
                $pdfOptions->set('isRemoteEnabled', false);

                $dompdf = new \Dompdf\Dompdf($pdfOptions);
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();

                $filename = 'Billet_' . $ticket->getNumero() . '.pdf';

                return new Response(
                    $dompdf->output(),
                    200,
                    [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                    ]
                );
            } catch (\Exception $e) {
                // Continue to HTML fallback
            }
        }

        // Fallback: printable HTML
        return new Response($html);
    }

    #[Route('/client/ticket/{id}/envoyer', name: 'app_client_ticket_email', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function sendTicketEmail(Ticket $ticket, MailerInterface $mailer): Response
    {
        $user = $this->getUser();
        $client = $user->getClient();

        if (!$client || $ticket->getReservation()->getClient() !== $client) {
            return $this->json(['success' => false, 'message' => 'Accès non autorisé'], 403);
        }

        try {
            $html = $this->renderView('ticket/pdf.html.twig', [
                'ticket' => $ticket,
            ]);

            $pdfContent = null;
            if (class_exists('\Dompdf\Dompdf')) {
                try {
                    $pdfOptions = new \Dompdf\Options();
                    $pdfOptions->set('defaultFont', 'DejaVu Sans');
                    $pdfOptions->set('isRemoteEnabled', false);

                    $dompdf = new \Dompdf\Dompdf($pdfOptions);
                    $dompdf->loadHtml($html);
                    $dompdf->setPaper('A4', 'portrait');
                    $dompdf->render();
                    $pdfContent = $dompdf->output();
                } catch (\Exception $e) {
                    // PDF generation failed
                }
            }

            $email = (new Email())
                ->from('noreply@aeromanager.com')
                ->to($user->getEmail())
                ->subject('Votre billet - ' . $ticket->getNumero())
                ->html($this->renderView('ticket/email.html.twig', [
                    'ticket' => $ticket,
                    'client' => $client,
                ]));

            if ($pdfContent) {
                $email->attach($pdfContent, 'Billet_' . $ticket->getNumero() . '.pdf', 'application/pdf');
            }

            $mailer->send($email);

            return $this->json([
                'success' => true,
                'message' => 'Billet envoyé avec succès à ' . $user->getEmail()
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi: ' . $e->getMessage()
            ], 500);
        }
    }

    // Admin routes
    #[Route('/admin/tickets', name: 'app_ticket_admin_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminIndex(TicketRepository $ticketRepository): Response
    {
        $tickets = $ticketRepository->findBy([], ['dateCreation' => 'DESC']);

        return $this->render('administrateur/ticket/index.html.twig', [
            'tickets' => $tickets,
        ]);
    }

    #[Route('/admin/ticket/new', name: 'app_ticket_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $ticket = new Ticket();
        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$ticket->getNumero()) {
                $ticket->setNumero('TKT-' . strtoupper(uniqid()));
            }

            if (!$ticket->getIdTicket()) {
                $ticket->setIdTicket(random_int(100000, 999999));
            }

            if (!$ticket->getDateCreation()) {
                $ticket->setDateCreation(new \DateTime());
            }

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

    #[Route('/admin/ticket/{id}', name: 'app_ticket_admin_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminShow(Ticket $ticket): Response
    {
        return $this->render('administrateur/ticket/show.html.twig', [
            'ticket' => $ticket,
        ]);
    }

    #[Route('/admin/ticket/{id}/edit', name: 'app_ticket_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
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

    #[Route('/admin/ticket/{id}', name: 'app_ticket_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
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
