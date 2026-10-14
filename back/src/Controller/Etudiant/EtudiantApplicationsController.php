<?php

namespace App\Controller\Etudiant;

use App\Entity\Users\Personnel;
use App\Repository\EtudiantRepository;
use App\Repository\EtudiantScolariteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class EtudiantApplicationsController extends AbstractController
{
    public function __construct(
        private readonly EtudiantRepository $etudiantRepository,
        private readonly EtudiantScolariteRepository $etudiantScolariteRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/api/etudiants/bulk-applications', name: 'app_etudiants_bulk_applications', methods: ['POST'])]
    #[Route('/api/etudiant_applications/bulk', name: 'app_etudiant_applications_bulk', methods: ['POST'])]
    public function updateBulkApplications(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof Personnel) {
            return $this->json([
                'message' => 'Action réservée aux membres du personnel connectés.'
            ], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['message' => 'Requête JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $etudiantIds = $data['etudiantIds'] ?? [];
        $anneeId = isset($data['anneeId']) ? (int) $data['anneeId'] : null;
        $departementId = isset($data['departementId']) ? (int) $data['departementId'] : null;
        $anneeUniversitaireId = isset($data['anneeUniversitaireId']) ? (int) $data['anneeUniversitaireId'] : null;
        $action = $data['action'] ?? 'set'; // 'add', 'remove', 'set'
        $applications = $data['applications'] ?? [];

        if (!is_array($applications)) {
            return $this->json(['message' => 'Le champ applications doit être un tableau.'], Response::HTTP_BAD_REQUEST);
        }

        // Normalize input applications
        $inputApps = array_values(array_unique(array_filter(array_map(function ($app) {
            $low = strtolower(trim((string) $app));
            return $low === 'intranet' ? 'intranet' : $low;
        }, $applications))));

        /** @var \App\Entity\Users\Etudiant[] $etudiants */
        $etudiants = [];

        if (!empty($etudiantIds) && is_array($etudiantIds)) {
            $etudiants = $this->etudiantRepository->findBy(['id' => $etudiantIds]);
        } elseif ($anneeId !== null) {
            $qb = $this->etudiantScolariteRepository->createQueryBuilder('es')
                ->select('DISTINCT e')
                ->join('es.etudiant', 'e')
                ->join('es.scolariteSemestre', 'ss')
                ->join('ss.semestre', 's')
                ->join('s.annee', 'a')
                ->where('a.id = :anneeId')
                ->andWhere('es.actif = true')
                ->setParameter('anneeId', $anneeId);

            if ($departementId) {
                $qb->join('es.departement', 'd')
                    ->andWhere('d.id = :deptId')
                    ->setParameter('deptId', $departementId);
            }
            if ($anneeUniversitaireId) {
                $qb->join('es.anneeUniversitaire', 'au')
                    ->andWhere('au.id = :auId')
                    ->setParameter('auId', $anneeUniversitaireId);
            }

            $etudiants = $qb->getQuery()->getResult();
        } else {
            return $this->json(['message' => 'Aucun étudiant ou année spécifié.'], Response::HTTP_BAD_REQUEST);
        }

        if (empty($etudiants)) {
            return $this->json([
                'success' => true,
                'count' => 0,
                'message' => 'Aucun étudiant concerné par cette mise à jour.'
            ]);
        }

        foreach ($etudiants as $etudiant) {
            $rawApps = $etudiant->getApplications() ?? ['Intranet'];
            $currentApps = array_values(array_unique(array_map(function ($app) {
                $low = strtolower(trim((string) $app));
                return $low === 'intranet' ? 'intranet' : $low;
            }, $rawApps)));

            if ($action === 'add') {
                $merged = array_values(array_unique(array_merge($currentApps, $inputApps)));
            } elseif ($action === 'remove') {
                $merged = array_values(array_diff($currentApps, $inputApps));
                // Toujours conserver au minimum 'intranet'
                if (empty($merged)) {
                    $merged = ['intranet'];
                }
            } else { // 'set'
                $merged = $inputApps;
                if (empty($merged)) {
                    $merged = ['intranet'];
                }
            }

            $etudiant->setApplications($merged);
            $this->etudiantRepository->save($etudiant, false);
        }

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'count' => count($etudiants),
            'applications' => $inputApps,
            'action' => $action,
            'message' => sprintf('Applications mises à jour avec succès pour %d étudiant(s).', count($etudiants)),
        ]);
    }
}
