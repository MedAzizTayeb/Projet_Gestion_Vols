<?php

namespace App\Controller;

use App\Entity\Aeroport;
use App\Form\AeroportType;
use App\Repository\AeroportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/aeroports')]
#[IsGranted('ROLE_ADMIN')]
class AdminAeroportController extends AbstractController
{
    #[Route('/', name: 'app_admin_aeroports_index', methods: ['GET'])]
    public function index(AeroportRepository $aeroportRepository): Response
    {
        $aeroports = $aeroportRepository->findAll();

        return $this->render('administrateur/aeroports/index.html.twig', [
            'aeroports' => $aeroports,
        ]);
    }

    #[Route('/new', name: 'app_admin_aeroports_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $aeroport = new Aeroport();
        $form = $this->createForm(AeroportType::class, $aeroport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($aeroport);
            $entityManager->flush();

            $this->addFlash('success', 'Aéroport créé avec succès !');
            return $this->redirectToRoute('app_admin_aeroports_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('administrateur/aeroports/new.html.twig', [
            'aeroport' => $aeroport,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_aeroports_show', methods: ['GET'])]
    public function show(Aeroport $aeroport): Response
    {
        return $this->render('administrateur/aeroports/show.html.twig', [
            'aeroport' => $aeroport,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_aeroports_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Aeroport $aeroport, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AeroportType::class, $aeroport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Aéroport modifié avec succès !');
            return $this->redirectToRoute('app_admin_aeroports_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('administrateur/aeroports/edit.html.twig', [
            'aeroport' => $aeroport,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_aeroports_delete', methods: ['POST'])]
    public function delete(Request $request, Aeroport $aeroport, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$aeroport->getId(), $request->getPayload()->getString('_token'))) {
            try {
                $entityManager->remove($aeroport);
                $entityManager->flush();

                $this->addFlash('success', 'Aéroport supprimé avec succès !');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Impossible de supprimer cet aéroport. Il est peut-être lié à des vols existants.');
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide. Suppression refusée.');
        }

        return $this->redirectToRoute('app_admin_aeroports_index', [], Response::HTTP_SEE_OTHER);
    }
}
