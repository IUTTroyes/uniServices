<?php

namespace IntranetBundle\Controller;

use App\Repository\Structure\StructureSemestreRepository;
use IntranetBundle\Commission\Contract\CommissionServiceInterface;
use IntranetBundle\Commission\Exception\CommissionNotSupportedException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/intranet/commissions', name: 'api_intranet_commissions_')]
class CommissionController extends AbstractController
{
    public function __construct(
        private readonly CommissionServiceInterface $commissionService,
        private readonly StructureSemestreRepository $structureSemestreRepository,
    ) {
    }

    #[Route('/semestre/{semestreId}', name: 'by_semestre', methods: ['GET'])]
    public function getBySemestre(int $semestreId): JsonResponse
    {
        $semestre = $this->structureSemestreRepository->find($semestreId);
        if (null === $semestre) {
            return new JsonResponse(['error' => 'Semestre introuvable.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $view = $this->commissionService->getCommissionView($semestre);

            return new JsonResponse($view, Response::HTTP_OK);
        } catch (CommissionNotSupportedException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'Une erreur est survenue lors du calcul de la commission : '.$e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
