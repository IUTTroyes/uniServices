<?php

namespace App\Controller\Edt;

use App\Repository\Edt\EdtProgressionRepository;
use App\Repository\Previsionnel\PrevisionnelRepository;
use App\Services\Edt\GenereSlots;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
        //todo: ajouter un voter pour vérifier que l'utilisateur a le droit de dupliquer une ressource
        //todo: test des droits avec un voters

        $previsionnels = $this->previsionnelRepository->findAll(); //todo: filtrer sur département de l'user connecté
        $nbSlots = $this->genereSlots->genereAllSlots($previsionnels);

        return \App\Utils\JsonResponse::Success($nbSlots. ' slot(s) généré(s) avec succès');
    }
}
