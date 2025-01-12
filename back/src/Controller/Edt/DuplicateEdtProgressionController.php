<?php

namespace App\Controller\Edt;

use App\Entity\Edt\EdtProgression;
use App\Repository\Edt\EdtProgressionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DuplicateEdtProgressionController extends AbstractController
{
    public function __construct(
        protected EdtProgressionRepository $edtProgressionRepository,
        protected EntityManagerInterface $entityManager)
    {
    }

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $original = $this->edtProgressionRepository->find($id);

        if (!$original) {
            return new JsonResponse(['error' => 'EdtProgression not found'], 404);
        }

        $duplicate = clone $original;
        $this->entityManager->persist($duplicate);
        $this->entityManager->flush();

        return new JsonResponse($duplicate, 201);
    }
}
