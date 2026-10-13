<?php

declare(strict_types=1);

namespace IntranetBundle\Service\Export;

use App\Entity\Structure\StructureGroupe;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Users\Etudiant;
use App\Enum\TypeGroupeEnum;
use App\Repository\Structure\StructureGroupeRepository;
use App\Repository\Structure\StructureSemestreRepository;
use App\Service\Pdf\PdfExportOptions;
use App\Service\Pdf\PdfExportService;
use Davidannebicque\HtmlToSpreadsheetBundle\Spreadsheet\SpreadsheetRenderer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListingEtudiantExportService
{
    public function __construct(
        private readonly PdfExportService $pdfService,
        private readonly SpreadsheetRenderer $spreadsheetRenderer,
        private readonly EntityManagerInterface $entityManager,
        private readonly StructureSemestreRepository $semestreRepository,
        private readonly StructureGroupeRepository $groupeRepository,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    /**
     * @return StructureGroupe[]
     */
    public function getGroupesForExport(int $semestreId, ?int $groupeId = null, ?string $type = null): array
    {
        if ($groupeId !== null) {
            $groupe = $this->groupeRepository->find($groupeId);
            return $groupe ? [$groupe] : [];
        }

        $qb = $this->groupeRepository->createQueryBuilder('g')
            ->join('g.semestres', 's')
            ->where('s.id = :semestreId')
            ->setParameter('semestreId', $semestreId);

        if ($type !== null && $type !== '' && $type !== 'ALL') {
            $enumType = TypeGroupeEnum::tryFrom($type);
            $qb->andWhere('g.type = :type')
                ->setParameter('type', $enumType ?? $type);
        }

        $qb->orderBy('g.ordre', 'ASC')
            ->addOrderBy('g.libelle', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Gather student data for a group, sorted alphabetically.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getEtudiantsDataForGroupe(
        StructureGroupe $groupe,
        ?StructureSemestre $semestre = null,
        bool $includePhoto = false
    ): array {
        $etudiantsMap = [];

        // 1. Through EtudiantScolariteSemestre relation
        foreach ($groupe->getScolariteSemestres() as $scolSemestre) {
            if ($semestre !== null && $scolSemestre->getSemestre()?->getId() !== $semestre->getId()) {
                continue;
            }
            $etudiant = $scolSemestre->getScolarite()?->getEtudiant();
            if ($etudiant instanceof Etudiant && !isset($etudiantsMap[$etudiant->getId()])) {
                $etudiantsMap[$etudiant->getId()] = $etudiant;
            }
        }

        // 2. Direct relation if populated
        foreach ($groupe->getEtudiants() as $etudiant) {
            if ($etudiant instanceof Etudiant && !isset($etudiantsMap[$etudiant->getId()])) {
                $etudiantsMap[$etudiant->getId()] = $etudiant;
            }
        }

        // Sort by nom, prenom
        uasort($etudiantsMap, function (Etudiant $a, Etudiant $b): int {
            $cmp = strcasecmp((string) $a->getNom(), (string) $b->getNom());
            return $cmp === 0 ? strcasecmp((string) $a->getPrenom(), (string) $b->getPrenom()) : $cmp;
        });

        $result = [];
        foreach ($etudiantsMap as $etudiant) {
            $groupesList = [];
            foreach ($etudiant->getGroupes() as $g) {
                $groupesList[] = $g->getLibelle();
            }

            $item = [
                'id' => $etudiant->getId(),
                'nom' => $etudiant->getNom(),
                'prenom' => $etudiant->getPrenom(),
                'numEtudiant' => $etudiant->getNumEtudiant(),
                'mailUniv' => $etudiant->getMailUniv(),
                'bac' => $etudiant->getBac()?->getLibelle() ?? '-',
                'groupes' => implode(', ', $groupesList),
                'photoName' => $etudiant->getPhotoName(),
            ];

            if ($includePhoto) {
                $item['photoBase64'] = $this->resolvePhotoBase64($etudiant->getPhotoName());
            }

            $result[] = $item;
        }

        return $result;
    }

    /**
     * Export listing (émargement) in PDF, Excel, or CSV.
     */
    public function exportListing(
        StructureSemestre $semestre,
        array $groupes,
        string $format = 'pdf'
    ): Response {
        $context = $this->buildContext($semestre, $groupes, false);
        $cleanSemestreLibelle = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) $semestre->getLibelle());
        $baseFilename = 'listing_emargement_' . $cleanSemestreLibelle . '_' . date('Ymd');

        return match (strtolower($format)) {
            'xlsx', 'excel' => $this->spreadsheetRenderer->renderFromTemplate(
                '@Intranet/export/listing_etudiants.xlsx.twig',
                $context,
                $baseFilename . '.xlsx'
            ),
            'csv' => $this->generateCsvListing($context, $baseFilename . '.csv'),
            default => $this->pdfService->generateStreamedResponse(
                '@Intranet/export/listing_etudiants.html.twig',
                $context,
                $baseFilename,
                PdfExportOptions::create()
                    ->setLandscape(true)
                    ->setTitle("Feuille d'émargement")
                    ->setSubtitle($semestre->getLibelle())
                    ->setDepartement($context['departement']['libelle'] ?? null)
                    ->setAnneeUniversitaire($context['anneeUniversitaire']['libelle'] ?? null)
                    ->setWithHeader(false)
                    ->setWithFooter(true)
                    ->setMargins(0.8, 1.2, 0.8, 0.8)
            ),
        };
    }

    /**
     * Export photo trombinoscope in PDF via Gotenberg.
     */
    public function exportTrombinoscope(
        StructureSemestre $semestre,
        array $groupes
    ): Response {
        $context = $this->buildContext($semestre, $groupes, true);
        $cleanSemestreLibelle = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) $semestre->getLibelle());
        $baseFilename = 'trombinoscope_' . $cleanSemestreLibelle . '_' . date('Ymd');

        $options = PdfExportOptions::create()
            ->setLandscape(false)
            ->setTitle("Trombinoscope Étudiants")
            ->setSubtitle($semestre->getLibelle())
            ->setDepartement($context['departement']['libelle'] ?? null)
            ->setAnneeUniversitaire($context['anneeUniversitaire']['libelle'] ?? null)
            ->setHeaderTemplate('@Intranet/export/pdf/_header.html.twig')
            ->setFooterTemplate('@Intranet/export/pdf/_footer.html.twig')
            ->setWithHeader(true)
            ->setWithFooter(true)
            ->setMargins(1.4, 1.2, 0.8, 0.8);

        return $this->pdfService->generateStreamedResponse(
            '@Intranet/export/trombinoscope_etudiants.html.twig',
            $context,
            $baseFilename,
            $options
        );
    }

    /**
     * Export custom fields selection in Excel, PDF, or CSV.
     *
     * @param array<int, string> $champs
     */
    public function exportPersonnalise(
        StructureSemestre $semestre,
        array $groupes,
        array $champs,
        string $format = 'xlsx'
    ): Response {
        $availableColonnes = [
            'numEtudiant' => 'N° Étudiant',
            'nom' => 'Nom',
            'prenom' => 'Prénom',
            'mailUniv' => 'Email Universitaire',
            'bac' => 'Bac / Origine',
            'groupes' => 'Groupes',
        ];

        $selectedColonnes = [];
        foreach ($champs as $champKey) {
            if (isset($availableColonnes[$champKey])) {
                $selectedColonnes[$champKey] = $availableColonnes[$champKey];
            }
        }

        if (empty($selectedColonnes)) {
            $selectedColonnes = [
                'numEtudiant' => 'N° Étudiant',
                'nom' => 'Nom',
                'prenom' => 'Prénom',
                'mailUniv' => 'Email Universitaire',
            ];
        }

        $context = $this->buildContext($semestre, $groupes, false);
        $context['colonnes'] = $selectedColonnes;
        $context['champs'] = array_keys($selectedColonnes);

        $cleanSemestreLibelle = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) $semestre->getLibelle());
        $baseFilename = 'export_personnalise_' . $cleanSemestreLibelle . '_' . date('Ymd');

        return match (strtolower($format)) {
            'pdf' => $this->pdfService->generateStreamedResponse(
                '@Intranet/export/export_personnalise.html.twig',
                $context,
                $baseFilename,
                PdfExportOptions::create()
                    ->setLandscape(true)
                    ->setTitle("Export Personnalisé Étudiants")
                    ->setSubtitle($semestre->getLibelle())
                    ->setDepartement($context['departement']['libelle'] ?? null)
                    ->setAnneeUniversitaire($context['anneeUniversitaire']['libelle'] ?? null)
                    ->setWithHeader(true)
                    ->setWithFooter(true)
                    ->setMargins(1.4, 1.2, 0.8, 0.8)
            ),
            'csv' => $this->generateCsvCustom($context, $selectedColonnes, $baseFilename . '.csv'),
            default => $this->spreadsheetRenderer->renderFromTemplate(
                '@Intranet/export/export_personnalise.xlsx.twig',
                $context,
                $baseFilename . '.xlsx'
            ),
        };
    }

    /**
     * Helper to assemble template context.
     *
     * @param StructureGroupe[] $groupes
     */
    private function buildContext(
        StructureSemestre $semestre,
        array $groupes,
        bool $includePhoto = false
    ): array {
        $data = [];
        foreach ($groupes as $groupe) {
            $data[] = [
                'groupe' => [
                    'id' => $groupe->getId(),
                    'libelle' => $groupe->getLibelle(),
                    'type' => $groupe->getType() instanceof TypeGroupeEnum ? $groupe->getType()->value : (string) $groupe->getType(),
                ],
                'etudiants' => $this->getEtudiantsDataForGroupe($groupe, $semestre, $includePhoto),
            ];
        }

        $annee = $semestre->getAnnee();
        $pn = $annee?->getPn();
        $diplome = $semestre->getDiplome() ?: $pn?->getDiplome();
        $departement = $diplome?->getDepartement();
        $anneeUniv = $pn?->getAnneeUniversitaire();

        return [
            'semestre' => [
                'id' => $semestre->getId(),
                'libelle' => $semestre->getLibelle(),
            ],
            'diplome' => $diplome ? [
                'id' => $diplome->getId(),
                'libelle' => $diplome->getLibelle(),
            ] : null,
            'departement' => $departement ? [
                'id' => $departement->getId(),
                'libelle' => $departement->getLibelle(),
            ] : null,
            'anneeUniversitaire' => [
                'id' => $anneeUniv?->getId(),
                'libelle' => $anneeUniv?->getLibelle() ?? (date('Y') . '-' . ((int) date('Y') + 1)),
            ],
            'data' => $data,
        ];
    }

    /**
     * Convert local photo to base64 data URI for self-contained PDF rendering.
     */
    private function resolvePhotoBase64(?string $photoName): ?string
    {
        $candidatePaths = [];
        if ($photoName !== null && $photoName !== '') {
            $candidatePaths[] = $this->projectDir . '/../shared/images/photos_etudiants/' . $photoName;
            $candidatePaths[] = $this->projectDir . '/public/uploads/etudiants/' . $photoName;
            $candidatePaths[] = $this->projectDir . '/public/common-images/photos_etudiants/' . $photoName;
        }

        $candidatePaths[] = $this->projectDir . '/../shared/images/photos_etudiants/noimage.png';

        foreach ($candidatePaths as $path) {
            if (file_exists($path) && is_readable($path)) {
                $content = file_get_contents($path);
                if ($content !== false) {
                    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                    $mime = match ($ext) {
                        'jpg', 'jpeg' => 'image/jpeg',
                        'png' => 'image/png',
                        'webp' => 'image/webp',
                        'gif' => 'image/gif',
                        default => 'image/png',
                    };
                    return 'data:' . $mime . ';base64,' . base64_encode($content);
                }
            }
        }

        return null;
    }

    /**
     * Generate standard CSV listing response.
     */
    private function generateCsvListing(array $context, string $filename): Response
    {
        $response = new StreamedResponse(function () use ($context): void {
            $handle = fopen('php://output', 'w+');
            if ($handle === false) {
                return;
            }
            // UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Groupe', '#', 'N° Étudiant', 'Nom', 'Prénom', 'Email Universitaire', 'Bac'], ';');

            foreach ($context['data'] as $groupData) {
                $groupeLibelle = $groupData['groupe']['libelle'];
                $index = 1;
                foreach ($groupData['etudiants'] as $etudiant) {
                    fputcsv($handle, [
                        $groupeLibelle,
                        $index++,
                        $etudiant['numEtudiant'],
                        $etudiant['nom'],
                        $etudiant['prenom'],
                        $etudiant['mailUniv'],
                        $etudiant['bac'],
                    ], ';');
                }
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename));

        return $response;
    }

    /**
     * Generate custom CSV response.
     */
    private function generateCsvCustom(array $context, array $colonnes, string $filename): Response
    {
        $response = new StreamedResponse(function () use ($context, $colonnes): void {
            $handle = fopen('php://output', 'w+');
            if ($handle === false) {
                return;
            }
            // UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            $headers = array_merge(['Groupe', '#'], array_values($colonnes));
            fputcsv($handle, $headers, ';');

            $colKeys = array_keys($colonnes);

            foreach ($context['data'] as $groupData) {
                $groupeLibelle = $groupData['groupe']['libelle'];
                $index = 1;
                foreach ($groupData['etudiants'] as $etudiant) {
                    $row = [$groupeLibelle, $index++];
                    foreach ($colKeys as $key) {
                        $row[] = $etudiant[$key] ?? '';
                    }
                    fputcsv($handle, $row, ';');
                }
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename));

        return $response;
    }
}
