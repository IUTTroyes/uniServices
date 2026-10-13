<?php

declare(strict_types=1);

namespace IntranetBundle\Controller\Export;

use App\Entity\Structure\StructureGroupe;
use App\Entity\Structure\StructureSemestre;
use App\Repository\Structure\StructureGroupeRepository;
use App\Repository\Structure\StructureSemestreRepository;
use IntranetBundle\Service\Export\ListingEtudiantExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/intranet/export')]
class ListingEtudiantExportController extends AbstractController
{
    public function __construct(
        private readonly ListingEtudiantExportService $exportService,
        private readonly StructureSemestreRepository $semestreRepository,
        private readonly StructureGroupeRepository $groupeRepository,
    ) {
    }

    /**
     * Export listing (feuille d'émargement) for a single group in PDF, Excel, or CSV.
     */
    #[Route('/listing/groupe/{groupeId}', name: 'api_intranet_export_listing_groupe', methods: ['GET'])]
    public function exportGroupe(int $groupeId, Request $request): Response
    {
        $groupe = $this->groupeRepository->find($groupeId);
        if (!$groupe) {
            return new JsonResponse(['error' => 'Groupe introuvable'], Response::HTTP_NOT_FOUND);
        }

        // Identify semester
        $semestre = $groupe->getSemestres()->first() ?: null;
        $semestreIdParam = $request->query->getInt('semestre');
        if ($semestreIdParam > 0) {
            $semestre = $this->semestreRepository->find($semestreIdParam) ?: $semestre;
        }

        if (!$semestre) {
            return new JsonResponse(['error' => 'Aucun semestre associé à ce groupe'], Response::HTTP_BAD_REQUEST);
        }

        $format = (string) $request->query->get('format', 'pdf');

        return $this->exportService->exportListing($semestre, [$groupe], $format);
    }

    /**
     * Export listing for an entire semester (or filtered by group type) in PDF, Excel, or CSV.
     */
    #[Route('/listing/semestre/{semestreId}', name: 'api_intranet_export_listing_semestre', methods: ['GET'])]
    public function exportSemestre(int $semestreId, Request $request): Response
    {
        $semestre = $this->semestreRepository->find($semestreId);
        if (!$semestre) {
            return new JsonResponse(['error' => 'Semestre introuvable'], Response::HTTP_NOT_FOUND);
        }

        $type = $request->query->get('type');
        $groupeId = $request->query->get('groupeId') ? (int) $request->query->get('groupeId') : null;
        $format = (string) $request->query->get('format', 'pdf');

        $groupes = $this->exportService->getGroupesForExport($semestreId, $groupeId, $type);
        if (empty($groupes)) {
            return new JsonResponse(['error' => 'Aucun groupe trouvé pour les critères demandés'], Response::HTTP_NOT_FOUND);
        }

        return $this->exportService->exportListing($semestre, $groupes, $format);
    }

    /**
     * Export photo trombinoscope for a semester / group in PDF via Gotenberg.
     */
    #[Route('/trombinoscope/semestre/{semestreId}', name: 'api_intranet_export_trombinoscope_semestre', methods: ['GET'])]
    public function exportTrombinoscope(int $semestreId, Request $request): Response
    {
        $semestre = $this->semestreRepository->find($semestreId);
        if (!$semestre) {
            return new JsonResponse(['error' => 'Semestre introuvable'], Response::HTTP_NOT_FOUND);
        }

        $type = $request->query->get('type');
        $groupeId = $request->query->get('groupeId') ? (int) $request->query->get('groupeId') : null;

        $groupes = $this->exportService->getGroupesForExport($semestreId, $groupeId, $type);
        if (empty($groupes)) {
            return new JsonResponse(['error' => 'Aucun groupe trouvé pour les critères demandés'], Response::HTTP_NOT_FOUND);
        }

        return $this->exportService->exportTrombinoscope($semestre, $groupes);
    }

    /**
     * Custom export: choose columns and format.
     */
    #[Route('/personnalise', name: 'api_intranet_export_personnalise', methods: ['POST'])]
    public function exportPersonnalise(Request $request): Response
    {
        $payload = json_decode((string) $request->getContent(), true) ?? [];

        $semestreId = (int) ($payload['semestreId'] ?? 0);
        $semestre = $this->semestreRepository->find($semestreId);
        if (!$semestre) {
            return new JsonResponse(['error' => 'Semestre introuvable'], Response::HTTP_BAD_REQUEST);
        }

        $groupeId = !empty($payload['groupeId']) ? (int) $payload['groupeId'] : null;
        $type = !empty($payload['type']) ? (string) $payload['type'] : null;
        $format = (string) ($payload['format'] ?? 'xlsx');
        $champs = (array) ($payload['champs'] ?? ['numEtudiant', 'nom', 'prenom', 'mailUniv']);

        $groupes = $this->exportService->getGroupesForExport($semestreId, $groupeId, $type);
        if (empty($groupes)) {
            return new JsonResponse(['error' => 'Aucun groupe trouvé'], Response::HTTP_NOT_FOUND);
        }

        return $this->exportService->exportPersonnalise($semestre, $groupes, $champs, $format);
    }
}
