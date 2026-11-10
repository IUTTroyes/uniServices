<?php

namespace App\Controller\Edt;

use App\Repository\Edt\EdtProgressionRepository;
use IntranetBundle\Repository\Previsionnel\PrevisionnelRepository;
use App\Service\Edt\GenereSlots;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_SUPER_ADMIN')]
class EdtGenereSlotsController extends AbstractController
{
    public function __construct(
        protected GenereSlots $genereSlots,
        protected PrevisionnelRepository $previsionnelRepository,
        protected EntityManagerInterface $entityManager)
    {
    }

    #[Route('/api/edt_genere_slots', name: 'edt_progression_genere_slots', methods: ['POST'])]
    public function genereSlots(Request $request): Response
    {
        $previsionnels = $this->previsionnelRepository->findAll();
        $nbSlots = $this->genereSlots->genereAllSlots($previsionnels);

        return \App\Utils\JsonResponse::Success($nbSlots. ' slot(s) généré(s) avec succès');
    }
}
