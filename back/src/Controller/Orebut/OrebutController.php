<?php

declare(strict_types=1);

namespace App\Controller\Orebut;

use App\Repository\Structure\StructureDiplomeRepository;
use App\Service\Orebut\ImportReferentiel;
use App\Service\Orebut\OrebutClient;
use App\Service\Orebut\OrebutException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/orebut')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[\App\AnneeUniversitaire\ContexteAnnuel('independant', 'Référentiels nationaux, sans année universitaire.')]
final class OrebutController extends AbstractController
{
    #[Route('/specialites', name: 'orebut_specialites', methods: ['GET'])]
    public function specialites(OrebutClient $client): JsonResponse
    {
        try {
            return $this->json(array_map(static fn (array $s): array => [
                'sigle' => $s['sigle'],
                'libelle' => $s['libelle'],
                'parcours' => array_map(static fn (array $p): array => ['code' => $p['code'], 'libelle' => $p['libelle']], $s['parcours']),
            ], $client->specialites()));
        } catch (OrebutException $e) {
            return $this->json(['message' => $e->getMessage()], 502);
        }
    }

    #[Route('/synchronisation', name: 'orebut_synchronisation', methods: ['POST'])]
    public function synchroniser(Request $request, StructureDiplomeRepository $diplomes, ImportReferentiel $import): JsonResponse
    {
        $donnees = $request->toArray();
        $sigle = $donnees['specialite'] ?? null;
        if (!is_int($donnees['diplomeId'] ?? null) || !is_string($sigle) || trim($sigle) === '') {
            return $this->json(['message' => 'diplomeId et specialite sont requis.'], 400);
        }
        $diplome = $diplomes->find($donnees['diplomeId']) ?? throw $this->createNotFoundException('Diplôme introuvable.');
        $this->denyAccessUnlessGranted('CAN_EDIT_DIPLOME', $diplome);

        try {
            return $this->json($import->synchroniser(
                $diplome,
                trim($sigle),
                is_string($donnees['parcours'] ?? null) ? $donnees['parcours'] : null,
                // Sans demande explicite d'écriture, on ne fait que prévisualiser.
                ($donnees['simulation'] ?? true) !== false,
            ));
        } catch (OrebutException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }
    }
}
