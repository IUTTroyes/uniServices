<?php

namespace App\Controller;

use App\Entity\Apc\ApcReferentiel;
use App\Repository\Apc\ApcReferentielRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/apc/referentiel')]
class ApcReferentielController extends AbstractController
{
    public function __construct(
        private readonly ApcReferentielRepository $referentielRepository,
        private readonly SerializerInterface $serializer
    ) {
    }

    #[Route('', name: 'api_apc_referentiel_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $referentiels = $this->referentielRepository->findAll();
        return new JsonResponse(
            $this->serializer->serialize($referentiels, 'json', ['groups' => 'referentiel:read']),
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('', name: 'api_apc_referentiel_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $referentiel = new ApcReferentiel();
        $referentiel->setLibelle($data['libelle']);
        if (isset($data['description'])) {
            $referentiel->setDescription($data['description']);
        }
        
        $this->referentielRepository->save($referentiel, true);
        
        return new JsonResponse(
            $this->serializer->serialize($referentiel, 'json', ['groups' => 'referentiel:read']),
            Response::HTTP_CREATED,
            [],
            true
        );
    }

    #[Route('/sync', name: 'api_apc_referentiel_sync', methods: ['POST'])]
    public function sync(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        // Traitement de la synchronisation
        foreach ($data as $referentielData) {
            $existingReferentiel = $this->referentielRepository->findOneBy(['libelle' => $referentielData['libelle']]);
            
            if (!$existingReferentiel) {
                $referentiel = new ApcReferentiel();
                $referentiel->setLibelle($referentielData['libelle']);
                if (isset($referentielData['description'])) {
                    $referentiel->setDescription($referentielData['description']);
                }
                $this->referentielRepository->save($referentiel, true);
            }
        }
        
        return new JsonResponse(['message' => 'Synchronisation réussie'], Response::HTTP_OK);
    }
} 