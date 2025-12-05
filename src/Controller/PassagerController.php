<?php

namespace App\Controller;

use App\Entity\Passager;
use App\Form\PassagerType;
use App\Repository\PassagerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/passager')]
final class PassagerController extends AbstractController
{
    #[Route(name: 'app_passager_index', methods: ['GET'])]
    public function index(PassagerRepository $passagerRepository): Response
    {
        return $this->render('passager/index.html.twig', [
            'passagers' => $passagerRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_passager_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $passager = new Passager();
        $form = $this->createForm(PassagerType::class, $passager);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($passager);
            $entityManager->flush();

            return $this->redirectToRoute('app_passager_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('passager/new.html.twig', [
            'passager' => $passager,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_passager_show', methods: ['GET'])]
    public function show(Passager $passager): Response
    {
        return $this->render('passager/show.html.twig', [
            'passager' => $passager,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_passager_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Passager $passager, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PassagerType::class, $passager);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_passager_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('passager/edit.html.twig', [
            'passager' => $passager,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_passager_delete', methods: ['POST'])]
    public function delete(Request $request, Passager $passager, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$passager->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($passager);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_passager_index', [], Response::HTTP_SEE_OTHER);
    }
}
