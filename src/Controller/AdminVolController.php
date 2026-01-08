<?php

namespace App\Controller;

use App\Entity\Vol;
use App\Form\VolType;
use App\Repository\VolRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/vols')]
#[IsGranted('ROLE_ADMIN')]
class AdminVolController extends AbstractController
{
    #[Route('/', name: 'app_admin_vols_index', methods: ['GET'])]
    public function index(VolRepository $volRepository): Response
    {
        $vols = $volRepository->findAll();

        return $this->render('administrateur/vols/index.html.twig', [
            'vols' => $vols,
        ]);
    }

    #[Route('/new', name: 'app_admin_vols_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
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

    #[Route('/{id}', name: 'app_admin_vols_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Vol $vol): Response
    {
        return $this->render('administrateur/vols/show.html.twig', [
            'vol' => $vol,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_vols_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Vol $vol, EntityManagerInterface $entityManager): Response
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

    #[Route('/{id}/delete', name: 'app_admin_vols_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Vol $vol, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$vol->getId(), $request->getPayload()->getString('_token'))) {
            try {
                $entityManager->remove($vol);
                $entityManager->flush();

                $this->addFlash('success', 'Vol supprimé avec succès !');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Impossible de supprimer ce vol. Il est peut-être lié à des réservations existantes.');
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide. Suppression refusée.');
        }

        return $this->redirectToRoute('app_admin_vols_index', [], Response::HTTP_SEE_OTHER);
    }
}
