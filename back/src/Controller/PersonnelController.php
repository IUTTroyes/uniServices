<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Enum\StatutEnum;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class PersonnelController extends AbstractController
{
    #[Route('/api/statuts', name: 'api_statuts', methods: ['GET'])]
    public function getStatuts(): JsonResponse
    {
        $statuts = StatutEnum::getStatuts();
        return new JsonResponse($statuts);
    }
}
