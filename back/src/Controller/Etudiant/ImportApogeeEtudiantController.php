<?php

declare(strict_types=1);

namespace App\Controller\Etudiant;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ImportApogeeEtudiantController extends AbstractController
{
    #[Route('/api/etudiants/import_apogee', methods: ['POST'])]
    public function import(Request $request): Response
    {
        // Récupérer les données JSON envoyées dans la requête
        $data = $request->toArray();

        $anneeId = $data['anneeId'] ?? null;
        $semestreId = $data['semestreId'] ?? null;
        $anneeUnivId = $data['anneeUnivId'] ?? null;

        return new Response('ok');
    }

    public function getEtudiantsFromApogee(): array
    {
        return [];
    }
}
