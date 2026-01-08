<?php

namespace App\Controller;

use App\Entity\Administrateur;
use App\Form\AdministrateurType;
use App\Repository\AdministrateurRepository;
use App\Repository\VolRepository;
use App\Repository\AvionRepository;
use App\Repository\AeroportRepository;
use App\Repository\ReservationRepository;
use App\Repository\ClientRepository;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdministrateurController extends AbstractController
{
    // ==================== DASHBOARD ====================

    #[Route('/dashboard', name: 'app_admin_dashboard', methods: ['GET'])]
    public function dashboard(
        VolRepository $volRepository,
        AvionRepository $avionRepository,
        AeroportRepository $aeroportRepository,
        ReservationRepository $reservationRepository,
        ClientRepository $clientRepository,
        TicketRepository $ticketRepository
    ): Response {
        // FIXED: Query for today's flights
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');

        $volsAujourdhui = $volRepository->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->where('v.DateDepart >= :today')
            ->andWhere('v.DateDepart < :tomorrow')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->getQuery()
            ->getSingleScalarResult();

        // Statistiques générales
        $stats = [
            'total_vols' => $volRepository->count([]),
            'vols_aujourdhui' => $volsAujourdhui, // FIXED
            'total_avions' => $avionRepository->count([]),
            'avions_disponibles' => $avionRepository->count(['disponibilite' => true]),
            'total_aeroports' => $aeroportRepository->count([]),
            'total_reservations' => $reservationRepository->count([]),
            'reservations_confirmees' => $reservationRepository->count(['Satut' => 'confirmé']),
            'total_clients' => $clientRepository->count([]),
            'total_tickets' => $ticketRepository->count([]),
        ];

        // Derniers vols
        $derniersVols = $volRepository->findBy([], ['DateDepart' => 'DESC'], 5);

        // Dernières réservations
        $dernieresReservations = $reservationRepository->findBy([], ['DateRes' => 'DESC'], 5);

        return $this->render('administrateur/dashboard.html.twig', [
            'stats' => $stats,
            'derniers_vols' => $derniersVols,
            'dernieres_reservations' => $dernieresReservations,
        ]);
    }

    // ==================== GESTION DES ADMINISTRATEURS ====================

    #[Route('/administrateurs', name: 'app_administrateur_index', methods: ['GET'])]
    public function index(AdministrateurRepository $administrateurRepository): Response
    {
        return $this->render('administrateur/index.html.twig', [
            'administrateurs' => $administrateurRepository->findAll(),
        ]);
    }

    #[Route('/administrateur/new', name: 'app_administrateur_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $administrateur = new Administrateur();
        $form = $this->createForm(AdministrateurType::class, $administrateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash du mot de passe
            $hashedPassword = $passwordHasher->hashPassword(
                $administrateur,
                $form->get('plainPassword')->getData()
            );
            $administrateur->setPassword($hashedPassword);

            $entityManager->persist($administrateur);
            $entityManager->flush();

            $this->addFlash('success', 'Administrateur créé avec succès !');
            return $this->redirectToRoute('app_administrateur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('administrateur/new.html.twig', [
            'administrateur' => $administrateur,
            'form' => $form,
        ]);
    }

    #[Route('/administrateur/{id}', name: 'app_administrateur_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Administrateur $administrateur): Response
    {
        return $this->render('administrateur/show.html.twig', [
            'administrateur' => $administrateur,
        ]);
    }

    #[Route('/administrateur/{id}/edit', name: 'app_administrateur_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Administrateur $administrateur,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $form = $this->createForm(AdministrateurType::class, $administrateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Si un nouveau mot de passe est fourni
            $plainPassword = $form->get('plainPassword')->getData();
            if (!empty($plainPassword)) {
                $hashedPassword = $passwordHasher->hashPassword(
                    $administrateur,
                    $plainPassword
                );
                $administrateur->setPassword($hashedPassword);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Administrateur modifié avec succès !');
            return $this->redirectToRoute('app_administrateur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('administrateur/edit.html.twig', [
            'administrateur' => $administrateur,
            'form' => $form,
        ]);
    }

    #[Route('/administrateur/{id}/delete', name: 'app_administrateur_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Administrateur $administrateur, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$administrateur->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($administrateur);
            $entityManager->flush();

            $this->addFlash('success', 'Administrateur supprimé avec succès !');
        } else {
            $this->addFlash('error', 'Token CSRF invalide. Suppression refusée.');
        }

        return $this->redirectToRoute('app_administrateur_index', [], Response::HTTP_SEE_OTHER);
    }

    // ==================== GESTION DES RÉSERVATIONS ====================

    #[Route('/reservations', name: 'app_admin_reservations_index', methods: ['GET'])]
    public function reservationsIndex(ReservationRepository $reservationRepository): Response
    {
        return $this->render('administrateur/reservations/index.html.twig', [
            'reservations' => $reservationRepository->findAll(),
        ]);
    }

    #[Route('/reservations/{id}/valider', name: 'app_admin_reservations_valider', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function reservationsValider(Request $request, ReservationRepository $reservationRepository, EntityManagerInterface $entityManager, int $id): Response
    {
        $reservation = $reservationRepository->find($id);

        if (!$reservation) {
            $this->addFlash('error', 'Réservation introuvable');
            return $this->redirectToRoute('app_admin_reservations_index');
        }

        if ($this->isCsrfTokenValid('valider'.$reservation->getId(), $request->getPayload()->getString('_token'))) {
            $reservation->setSatut('confirmé');
            $entityManager->flush();

            $this->addFlash('success', 'Réservation validée avec succès !');
        }

        return $this->redirectToRoute('app_admin_reservations_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/reservations/{id}/annuler', name: 'app_admin_reservations_annuler', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function reservationsAnnuler(Request $request, ReservationRepository $reservationRepository, EntityManagerInterface $entityManager, int $id): Response
    {
        $reservation = $reservationRepository->find($id);

        if (!$reservation) {
            $this->addFlash('error', 'Réservation introuvable');
            return $this->redirectToRoute('app_admin_reservations_index');
        }

        if ($this->isCsrfTokenValid('annuler'.$reservation->getId(), $request->getPayload()->getString('_token'))) {
            $reservation->setSatut('annulé');
            $entityManager->flush();

            $this->addFlash('success', 'Réservation annulée avec succès !');
        }

        return $this->redirectToRoute('app_admin_reservations_index', [], Response::HTTP_SEE_OTHER);
    }

    // ==================== GESTION DES CLIENTS ====================

    #[Route('/clients', name: 'app_admin_clients_index', methods: ['GET'])]
    public function clientsIndex(ClientRepository $clientRepository): Response
    {
        return $this->render('administrateur/clients/index.html.twig', [
            'clients' => $clientRepository->findAll(),
        ]);
    }

    #[Route('/clients/{id}', name: 'app_admin_clients_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function clientsShow(ClientRepository $clientRepository, int $id): Response
    {
        $client = $clientRepository->find($id);

        if (!$client) {
            $this->addFlash('error', 'Client introuvable');
            return $this->redirectToRoute('app_admin_clients_index');
        }

        return $this->render('administrateur/clients/show.html.twig', [
            'client' => $client,
        ]);
    }

    // ==================== RAPPORTS ET STATISTIQUES ====================

    #[Route('/rapports', name: 'app_admin_rapports', methods: ['GET'])]
    public function rapports(
        VolRepository $volRepository,
        ReservationRepository $reservationRepository,
        TicketRepository $ticketRepository
    ): Response {
        // Statistiques par période
        $today = new \DateTime('today');
        $thisMonth = new \DateTime('first day of this month');
        $thisYear = new \DateTime('first day of January this year');

        $rapports = [
            'vols_aujourdhui' => $volRepository->count(['DateDepart' => $today]),
            'reservations_mois' => $reservationRepository->createQueryBuilder('r')
                ->select('COUNT(r.id)')
                ->where('r.DateRes >= :debut')
                ->setParameter('debut', $thisMonth)
                ->getQuery()
                ->getSingleScalarResult(),
            'tickets_annee' => $ticketRepository->createQueryBuilder('t')
                ->select('COUNT(t.id)')
                ->where('t.dateCreation >= :debut')
                ->setParameter('debut', $thisYear)
                ->getQuery()
                ->getSingleScalarResult(),
        ];

        return $this->render('administrateur/rapports.html.twig', [
            'rapports' => $rapports,
        ]);
    }
}
