<?php

declare(strict_types=1);

namespace App\Controller\Etudiant;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[\App\AnneeUniversitaire\ContexteAnnuel('annuel', 'Travail sur la maquette ou les données de l’année demandée.')]
class ImportApogeeEtudiantController extends AbstractController
{
    #[Route('/api/etudiants/import_apogee', methods: ['POST'])]
    #[Route('/api/import_apogee/etudiants', methods: ['POST'])]
    public function import(Request $request): Response
    {
        // Récupérer les données JSON envoyées dans la requête
        $data = $request->toArray();
        if (is_array($data)) $data['anneeUnivId'] = $request->attributes->get('_annee_universitaire_id');

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
