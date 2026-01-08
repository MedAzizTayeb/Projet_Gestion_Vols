<?php

namespace App\Controller;

use App\Entity\Passager;
use App\Repository\PassagerRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/passagers')]
class PassagerController extends AbstractController
{
    // NEW: Admin route to list all passengers
    #[Route('/', name: 'app_passager_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(PassagerRepository $passagerRepository): Response
    {
        $passagers = $passagerRepository->findAll();

        return $this->render('passager/index.html.twig', [
            'passagers' => $passagers,
        ]);
    }

    #[Route('/reservation/{reservationId}/ajouter', name: 'app_passager_add', requirements: ['reservationId' => '\d+'], methods: ['GET', 'POST'])]
    public function add(
        int $reservationId,
        Request $request,
        ReservationRepository $reservationRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $reservation = $reservationRepository->find($reservationId);

        if (!$reservation) {
            $this->addFlash('error', 'Réservation introuvable');
            return $this->redirectToRoute('app_client_reservations');
        }

        // Vérifier que la réservation appartient au client connecté
        $user = $this->getUser();
        if ($reservation->getClient() !== $user->getClient()) {
            $this->addFlash('error', 'Accès non autorisé');
            return $this->redirectToRoute('app_client_reservations');
        }

        // Récupérer le nombre de passagers prévu
        $nbPassagers = $request->getSession()->get('reservation_nb_passagers_' . $reservation->getId(), 1);

        // Vérifier combien de passagers sont déjà ajoutés
        $passagersExistants = $entityManager->getRepository(Passager::class)
            ->findBy(['reservation' => $reservation]);

        $passagersRestants = $nbPassagers - count($passagersExistants);

        if ($passagersRestants <= 0) {
            $this->addFlash('warning', 'Tous les passagers ont déjà été ajoutés');
            return $this->redirectToRoute('app_reservation_show', ['id' => $reservation->getId()]);
        }

        if ($request->isMethod('POST')) {
            try {
                $data = $request->request->all();

                // Créer les passagers
                foreach ($data['passagers'] ?? [] as $passagerData) {
                    if (empty($passagerData['nom']) || empty($passagerData['prenom'])) {
                        continue;
                    }

                    $passager = new Passager();
                    $passager->setNom($passagerData['nom']);
                    $passager->setPrenom($passagerData['prenom']);
                    $passager->setNumPassport($passagerData['numPassport'] ?? 'N/A');
                    $passager->setNationalite($passagerData['nationalite'] ?? 'Non renseigné');

                    // Gérer la date de naissance
                    $dateNaissance = !empty($passagerData['dateNaissance'])
                        ? new \DateTime($passagerData['dateNaissance'])
                        : new \DateTime('1990-01-01');
                    $passager->setDateNaissance($dateNaissance);

                    $passager->setBesoinsSpeciaux($passagerData['besoinsSpeciaux'] ?? null);
                    $passager->setPoidsBagages($passagerData['poidsBagages'] ?? '20.00');
                    $passager->setReservation($reservation);

                    $entityManager->persist($passager);
                }

                $entityManager->flush();

                $this->addFlash('success', 'Passagers ajoutés avec succès !');
                return $this->redirectToRoute('app_reservation_show', ['id' => $reservation->getId()]);

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'ajout des passagers : ' . $e->getMessage());
            }
        }

        return $this->render('passager/add.html.twig', [
            'reservation' => $reservation,
            'nbPassagers' => $nbPassagers,
            'passagersExistants' => count($passagersExistants),
            'passagersRestants' => $passagersRestants,
        ]);
    }

    #[Route('/reservation/{reservationId}', name: 'app_passager_list', requirements: ['reservationId' => '\d+'], methods: ['GET'])]
    public function list(
        int $reservationId,
        ReservationRepository $reservationRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $reservation = $reservationRepository->find($reservationId);

        if (!$reservation) {
            $this->addFlash('error', 'Réservation introuvable');
            return $this->redirectToRoute('app_client_reservations');
        }

        // Vérifier l'accès
        $user = $this->getUser();
        if ($reservation->getClient() !== $user->getClient()) {
            $this->addFlash('error', 'Accès non autorisé');
            return $this->redirectToRoute('app_client_reservations');
        }

        $passagers = $entityManager->getRepository(Passager::class)
            ->findBy(['reservation' => $reservation]);

        return $this->render('passager/list.html.twig', [
            'reservation' => $reservation,
            'passagers' => $passagers,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_passager_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Passager $passager,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        // Vérifier l'accès
        $user = $this->getUser();
        if ($passager->getReservation()->getClient() !== $user->getClient()) {
            $this->addFlash('error', 'Accès non autorisé');
            return $this->redirectToRoute('app_client_reservations');
        }

        if ($request->isMethod('POST')) {
            try {
                $passager->setNumPassport($request->request->get('numPassport'));
                $passager->setNationalite($request->request->get('nationalite'));
                $passager->setDateNaissance(new \DateTime($request->request->get('dateNaissance')));
                $passager->setBesoinsSpeciaux($request->request->get('besoinsSpeciaux'));
                $passager->setPoidsBagages($request->request->get('poidsBagages'));

                $entityManager->flush();

                $this->addFlash('success', 'Passager modifié avec succès !');
                return $this->redirectToRoute('app_passager_list', [
                    'reservationId' => $passager->getReservation()->getId()
                ]);

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la modification : ' . $e->getMessage());
            }
        }

        return $this->render('passager/edit.html.twig', [
            'passager' => $passager,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_passager_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Passager $passager,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        // Vérifier l'accès
        $user = $this->getUser();
        if ($passager->getReservation()->getClient() !== $user->getClient()) {
            $this->addFlash('error', 'Accès non autorisé');
            return $this->redirectToRoute('app_client_reservations');
        }

        if ($this->isCsrfTokenValid('delete'.$passager->getId(), $request->request->get('_token'))) {
            $reservationId = $passager->getReservation()->getId();

            $entityManager->remove($passager);
            $entityManager->flush();

            $this->addFlash('success', 'Passager supprimé avec succès');

            return $this->redirectToRoute('app_passager_list', ['reservationId' => $reservationId]);
        }

        $this->addFlash('error', 'Token CSRF invalide');
        return $this->redirectToRoute('app_client_reservations');
    }
}
