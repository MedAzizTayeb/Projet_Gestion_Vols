<?php

namespace App\Controller;

use App\Entity\Avion;
use App\Form\AvionType;
use App\Repository\AvionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/avions')]
#[IsGranted('ROLE_ADMIN')]
class AdminAvionController extends AbstractController
{
    #[Route('/', name: 'app_admin_avions_index', methods: ['GET'])]
    public function index(AvionRepository $avionRepository): Response
    {
        $avions = $avionRepository->findAll();

        return $this->render('administrateur/avions/index.html.twig', [
            'avions' => $avions,
        ]);
    }

    #[Route('/new', name: 'app_admin_avions_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
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

    #[Route('/{id}', name: 'app_admin_avions_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Avion $avion): Response
    {
        return $this->render('administrateur/avions/show.html.twig', [
            'avion' => $avion,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_avions_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Avion $avion, EntityManagerInterface $entityManager): Response
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

    #[Route('/{id}/delete', name: 'app_admin_avions_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Avion $avion, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$avion->getId(), $request->getPayload()->getString('_token'))) {
            try {
                $entityManager->remove($avion);
                $entityManager->flush();

                $this->addFlash('success', 'Avion supprimé avec succès !');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Impossible de supprimer cet avion. Il est peut-être lié à des vols existants.');
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide. Suppression refusée.');
        }

        return $this->redirectToRoute('app_admin_avions_index', [], Response::HTTP_SEE_OTHER);
    }
}
