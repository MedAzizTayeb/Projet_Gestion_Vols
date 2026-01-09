<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/client')]
#[IsGranted('ROLE_USER')]
class ClientController extends AbstractController
{
    #[Route('/dashboard', name: 'app_client_dashboard')]
    public function dashboard(): Response
    {
        $user = $this->getUser();
        $client = $user->getClient();

        if (!$client) {
            $this->addFlash('error', 'Profil client introuvable');
            return $this->redirectToRoute('app_logout');
        }

        return $this->render('client/dashboard.html.twig', [
            'client' => $client,
        ]);
    }

    #[Route('/profil', name: 'app_client_profil')]
    public function profil(): Response
    {
        $user = $this->getUser();
        $client = $user->getClient();

        if (!$client) {
            $this->addFlash('error', 'Profil client introuvable');
            return $this->redirectToRoute('app_client_dashboard');
        }

        return $this->render('client/profil.html.twig', [
            'client' => $client,
        ]);
    }

    #[Route('/profil/modifier', name: 'app_client_profil_edit', methods: ['GET', 'POST'])]
    public function editProfil(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $client = $user->getClient();

        if (!$client) {
            $this->addFlash('error', 'Profil client introuvable');
            return $this->redirectToRoute('app_client_dashboard');
        }

        if ($request->isMethod('POST')) {
            try {
                // Update user info
                $nom = $request->request->get('nom');
                $prenom = $request->request->get('prenom');
                $email = $request->request->get('email');
                $telephone = $request->request->get('telephone');

                if ($nom) $user->setNom($nom);
                if ($prenom) $user->setPrenom($prenom);
                if ($email) $user->setEmail($email);
                if ($telephone) $client->setTelephone($telephone);

                $entityManager->flush();

                $this->addFlash('success', 'Profil mis à jour avec succès !');
                return $this->redirectToRoute('app_client_profil');

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
            }
        }

        return $this->render('client/profil_edit.html.twig', [
            'client' => $client,
        ]);
    }

    #[Route('/profil/mot-de-passe', name: 'app_client_change_password', methods: ['GET', 'POST'])]
    public function changePassword(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            try {
                $currentPassword = $request->request->get('current_password');
                $newPassword = $request->request->get('new_password');
                $confirmPassword = $request->request->get('confirm_password');

                // Verify current password
                if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                    $this->addFlash('error', 'Le mot de passe actuel est incorrect');
                    return $this->redirectToRoute('app_client_change_password');
                }

                // Verify new password matches confirmation
                if ($newPassword !== $confirmPassword) {
                    $this->addFlash('error', 'Les nouveaux mots de passe ne correspondent pas');
                    return $this->redirectToRoute('app_client_change_password');
                }

                // Verify password strength
                if (strlen($newPassword) < 6) {
                    $this->addFlash('error', 'Le mot de passe doit contenir au moins 6 caractères');
                    return $this->redirectToRoute('app_client_change_password');
                }

                // Hash and update password
                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);

                $entityManager->flush();

                $this->addFlash('success', 'Mot de passe modifié avec succès !');
                return $this->redirectToRoute('app_client_profil');

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la modification : ' . $e->getMessage());
            }
        }

        return $this->render('client/change_password.html.twig');
    }

    #[Route('/reservations', name: 'app_client_reservations')]
    public function reservations(): Response
    {
        $user = $this->getUser();
        $client = $user->getClient();

        if (!$client) {
            $this->addFlash('error', 'Profil client introuvable');
            return $this->redirectToRoute('app_client_dashboard');
        }

        return $this->render('client/reservations.html.twig', [
            'client' => $client,
            'reservations' => $client->getReservations(),
        ]);
    }

    #[Route('/tickets', name: 'app_client_tickets')]
    public function tickets(): Response
    {
        return $this->redirectToRoute('app_ticket_index');
    }

    #[Route('/ticket/{id}', name: 'app_client_ticket_show', requirements: ['id' => '\d+'])]
    public function ticketShow(int $id): Response
    {
        return $this->redirectToRoute('app_ticket_show', ['id' => $id]);
    }

    #[Route('/ticket/{id}/telecharger', name: 'app_client_ticket_download', requirements: ['id' => '\d+'])]
    public function ticketDownload(int $id): Response
    {
        return $this->redirectToRoute('app_ticket_download', ['id' => $id]);
    }
}
