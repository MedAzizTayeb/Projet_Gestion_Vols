<?php

namespace App\Controller;

use App\Entity\Vol;
use App\Repository\VolRepository;
use App\Repository\AeroportRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/vols')]
class VolController extends AbstractController
{
    #[Route('/', name: 'app_vol_index', methods: ['GET'])]
    public function index(
        Request $request,
        VolRepository $volRepository,
        AeroportRepository $aeroportRepository
    ): Response {
        $qb = $volRepository->createQueryBuilder('v')
            ->where('v.DateDepart > :now')
            ->andWhere('v.placesDisponibles > 0')
            ->setParameter('now', new \DateTime())
            ->orderBy('v.DateDepart', 'ASC');

        // Filtres
        $departId = $request->query->get('depart');
        $arriveeId = $request->query->get('arrivee');
        $dateDepart = $request->query->get('date_depart');

        if ($departId) {
            $qb->andWhere('v.depart = :depart')
                ->setParameter('depart', $departId);
        }

        if ($arriveeId) {
            $qb->andWhere('v.arrivee = :arrivee')
                ->setParameter('arrivee', $arriveeId);
        }

        if ($dateDepart) {
            try {
                $date = new \DateTime($dateDepart);
                $dateEnd = (clone $date)->modify('+1 day');
                $qb->andWhere('v.DateDepart >= :dateStart')
                    ->andWhere('v.DateDepart < :dateEnd')
                    ->setParameter('dateStart', $date)
                    ->setParameter('dateEnd', $dateEnd);
            } catch (\Exception $e) {
                // Date invalide, ignorer le filtre
            }
        }

        $vols = $qb->getQuery()->getResult();
        $aeroports = $aeroportRepository->findAll();

        return $this->render('vol/index.html.twig', [
            'vols' => $vols,
            'aeroports' => $aeroports,
            'filters' => [
                'depart' => $departId,
                'arrivee' => $arriveeId,
                'date_depart' => $dateDepart,
            ]
        ]);
    }

    #[Route('/{id}', name: 'app_vol_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Vol $vol): Response
    {
        return $this->render('vol/show.html.twig', [
            'vol' => $vol,
        ]);
    }
}
