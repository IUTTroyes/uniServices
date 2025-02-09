<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Enum\StatutEnum;

class PersonnelController extends AbstractController
{
    #[Route('/api/statuts', name: 'api_statuts', methods: ['GET'])]
    public function getStatuts(): JsonResponse
    {
        $statuts = StatutEnum::getStatuts();
        return new JsonResponse($statuts);
    }
}
