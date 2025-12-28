<?php

namespace App\Controller;

use App\Entity\Administrateur;
use App\Entity\Vol;
use App\Entity\Avion;
use App\Entity\Aeroport;
use App\Entity\Reservation;
use App\Entity\Client;
use App\Form\AdministrateurType;
use App\Form\VolType;
use App\Form\AvionType;
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
        // Statistiques générales
        $stats = [
            'total_vols' => $volRepository->count([]),
            'vols_aujourdhui' => $volRepository->count([
                'DateDepart' => new \DateTime('today')
            ]),
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

    #[Route('/administrateur/{id}', name: 'app_administrateur_show', methods: ['GET'])]
    public function show(Administrateur $administrateur): Response
    {
        return $this->render('administrateur/show.html.twig', [
            'administrateur' => $administrateur,
        ]);
    }

    #[Route('/administrateur/{id}/edit', name: 'app_administrateur_edit', methods: ['GET', 'POST'])]
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

    #[Route('/administrateur/{id}', name: 'app_administrateur_delete', methods: ['POST'])]
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

    // ==================== GESTION DES VOLS ====================

    #[Route('/vols', name: 'app_admin_vols_index', methods: ['GET'])]
    public function volsIndex(VolRepository $volRepository): Response
    {
        return $this->render('administrateur/vols/index.html.twig', [
            'vols' => $volRepository->findAll(),
        ]);
    }

    #[Route('/vols/new', name: 'app_admin_vols_new', methods: ['GET', 'POST'])]
    public function volsNew(Request $request, EntityManagerInterface $entityManager): Response
    {
        $vol = new Vol();
        $form = $this->createForm(VolType::class, $vol);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Associer l'administrateur qui crée le vol
            $vol->setCreePar($this->getUser());

            $entityManager->persist($vol);
            $entityManager->flush();

            $this->addFlash('success', 'Vol créé avec succès !');
            return $this->redirectToRoute('app_admin_vols_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('administrateur/vols/new.html.twig', [
            'vol' => $vol,
            'form' => $form,
        ]);
    }

    #[Route('/vols/{id}/edit', name: 'app_admin_vols_edit', methods: ['GET', 'POST'])]
    public function volsEdit(Request $request, Vol $vol, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(VolType::class, $vol);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Vol modifié avec succès !');
            return $this->redirectToRoute('app_admin_vols_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('administrateur/vols/edit.html.twig', [
            'vol' => $vol,
            'form' => $form,
        ]);
    }

    #[Route('/vols/{id}', name: 'app_admin_vols_delete', methods: ['POST'])]
    public function volsDelete(Request $request, Vol $vol, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$vol->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($vol);
            $entityManager->flush();

            $this->addFlash('success', 'Vol supprimé avec succès !');
        }

        return $this->redirectToRoute('app_admin_vols_index', [], Response::HTTP_SEE_OTHER);
    }

    // ==================== GESTION DES AVIONS ====================

    #[Route('/avions', name: 'app_admin_avions_index', methods: ['GET'])]
    public function avionsIndex(AvionRepository $avionRepository): Response
    {
        return $this->render('administrateur/avions/index.html.twig', [
            'avions' => $avionRepository->findAll(),
        ]);
    }

    #[Route('/avions/new', name: 'app_admin_avions_new', methods: ['GET', 'POST'])]
    public function avionsNew(Request $request, EntityManagerInterface $entityManager): Response
    {
        $avion = new Avion();
        $form = $this->createForm(AvionType::class, $avion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Associer l'administrateur qui gère l'avion
            $avion->setGerePar($this->getUser());

            $entityManager->persist($avion);
            $entityManager->flush();

            $this->addFlash('success', 'Avion créé avec succès !');
            return $this->redirectToRoute('app_admin_avions_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('administrateur/avions/new.html.twig', [
            'avion' => $avion,
            'form' => $form,
        ]);
    }

    #[Route('/avions/{id}/edit', name: 'app_admin_avions_edit', methods: ['GET', 'POST'])]
    public function avionsEdit(Request $request, Avion $avion, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AvionType::class, $avion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Avion modifié avec succès !');
            return $this->redirectToRoute('app_admin_avions_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('administrateur/avions/edit.html.twig', [
            'avion' => $avion,
            'form' => $form,
        ]);
    }

    #[Route('/avions/{id}', name: 'app_admin_avions_delete', methods: ['POST'])]
    public function avionsDelete(Request $request, Avion $avion, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$avion->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($avion);
            $entityManager->flush();

            $this->addFlash('success', 'Avion supprimé avec succès !');
        }

        return $this->redirectToRoute('app_admin_avions_index', [], Response::HTTP_SEE_OTHER);
    }

    // ==================== GESTION DES RÉSERVATIONS ====================

    #[Route('/reservations', name: 'app_admin_reservations_index', methods: ['GET'])]
    public function reservationsIndex(ReservationRepository $reservationRepository): Response
    {
        return $this->render('administrateur/reservations/index.html.twig', [
            'reservations' => $reservationRepository->findAll(),
        ]);
    }

    #[Route('/reservations/{id}/valider', name: 'app_admin_reservations_valider', methods: ['POST'])]
    public function reservationsValider(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('valider'.$reservation->getId(), $request->getPayload()->getString('_token'))) {
            $reservation->setSatut('confirmé');
            $entityManager->flush();

            $this->addFlash('success', 'Réservation validée avec succès !');
        }

        return $this->redirectToRoute('app_admin_reservations_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/reservations/{id}/annuler', name: 'app_admin_reservations_annuler', methods: ['POST'])]
    public function reservationsAnnuler(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
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

    #[Route('/clients/{id}', name: 'app_admin_clients_show', methods: ['GET'])]
    public function clientsShow(Client $client): Response
    {
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
