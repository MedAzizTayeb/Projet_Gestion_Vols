<?php

namespace App\Controller;

use App\Entity\CategorieAvion;
use App\Form\CategorieAvionType;
use App\Repository\CategorieAvionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/categorie/avion')]
final class CategorieAvionController extends AbstractController
{
    #[Route(name: 'app_categorie_avion_index', methods: ['GET'])]
    public function index(CategorieAvionRepository $categorieAvionRepository): Response
    {
        return $this->render('categorie_avion/index.html.twig', [
            'categorie_avions' => $categorieAvionRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_categorie_avion_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $categorieAvion = new CategorieAvion();
        $form = $this->createForm(CategorieAvionType::class, $categorieAvion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($categorieAvion);
            $entityManager->flush();

            return $this->redirectToRoute('app_categorie_avion_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('categorie_avion/new.html.twig', [
            'categorie_avion' => $categorieAvion,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_categorie_avion_show', methods: ['GET'])]
    public function show(CategorieAvion $categorieAvion): Response
    {
        return $this->render('categorie_avion/show.html.twig', [
            'categorie_avion' => $categorieAvion,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_categorie_avion_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CategorieAvion $categorieAvion, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategorieAvionType::class, $categorieAvion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_categorie_avion_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('categorie_avion/edit.html.twig', [
            'categorie_avion' => $categorieAvion,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_categorie_avion_delete', methods: ['POST'])]
    public function delete(Request $request, CategorieAvion $categorieAvion, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$categorieAvion->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($categorieAvion);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_categorie_avion_index', [], Response::HTTP_SEE_OTHER);
    }
}
