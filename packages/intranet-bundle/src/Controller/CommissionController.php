<?php

declare(strict_types=1);

namespace IntranetBundle\Controller;

use App\Entity\Structure\StructureSemestre;
use App\Entity\Users\Personnel;
use App\Repository\Structure\StructureSemestreRepository;
use Doctrine\ORM\OptimisticLockException;
use IntranetBundle\Commission\Service\SousCommissionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\HttpKernel\Exception\{BadRequestHttpException, ConflictHttpException};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/intranet/commissions', name: 'api_intranet_commissions_')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class CommissionController extends AbstractController
{
    public function __construct(private readonly SousCommissionService $service, private readonly StructureSemestreRepository $semestres) {}

    private function semestre(int $id): StructureSemestre
    {
        $semestre = $this->semestres->find($id) ?? throw $this->createNotFoundException('Semestre introuvable.');
        $department = $semestre->getDepartement();
        if (!$this->getUser() instanceof Personnel || (!$this->isGranted('CAN_EDIT_SEMESTRE', $semestre)
            && ($department === null || !$this->isGranted('ROLE_SCOLARITE', $department)))) throw $this->createAccessDeniedException();
        return $semestre;
    }

    #[Route('/semestre/{semestreId}', name: 'by_semestre', methods: ['GET'])]
    public function getBySemestre(int $semestreId): JsonResponse { return $this->json($this->service->consulter($this->semestre($semestreId))); }

    #[Route('/semestre/{semestreId}/valider', name: 'valider', methods: ['POST'])]
    public function valider(int $semestreId, Request $request): JsonResponse
    {
        $semestre = $this->semestre($semestreId);
        $data = $request->toArray();
        if (!is_string($data['empreinte'] ?? null) || !preg_match('/^[a-f0-9]{64}$/', $data['empreinte']) || !is_array($data['decisions'] ?? [])) throw new BadRequestHttpException('Empreinte et décisions invalides.');
        try { return $this->json($this->service->valider($semestre, $this->getUser(), $data['empreinte'], $data['decisions'] ?? [])); }
        catch (OptimisticLockException $e) { throw new ConflictHttpException('Modification concurrente : rechargez les résultats.', $e); }
    }

    #[Route('/semestre/{semestreId}/rouvrir', name: 'rouvrir', methods: ['POST'])]
    public function rouvrir(int $semestreId, Request $request): JsonResponse
    {
        $semestre = $this->semestre($semestreId);
        $data = $request->toArray();
        if (!is_string($data['motif'] ?? null) || !is_int($data['numeroRevision'] ?? null)) throw new BadRequestHttpException('Motif et numéro de révision requis.');
        return $this->json($this->service->rouvrir($semestre, $this->getUser(), $data['numeroRevision'], $data['motif']));
    }

    #[Route('/semestre/{semestreId}/historique', name: 'historique', methods: ['GET'])]
    public function historique(int $semestreId): JsonResponse { return $this->json($this->service->historique($this->semestre($semestreId))); }
}
