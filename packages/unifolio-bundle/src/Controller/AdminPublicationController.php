<?php

namespace UnifolioBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use UnifolioBundle\Entity\PortfolioPublication;
use UnifolioBundle\Repository\PortfolioPublicationRepository;
use UnifolioBundle\Security\PublicationVoter;
use UnifolioBundle\Security\UnifolioVoter;
use UnifolioBundle\Service\Publication\PublicationManager;
use UnifolioBundle\Service\Publication\PublicationNormalizer;
use UnifolioBundle\Service\Trace\UnifolioValidationException;

/** Modération des portfolios publiés : retrait immédiat avec motif, rétablissement. */
#[Route('/api/unifolio/admin/publications')]
#[IsGranted(UnifolioVoter::MODERER)]
class AdminPublicationController extends AbstractController
{
    private const UUID = '[0-9a-fA-F-]{36}';

    public function __construct(
        private readonly PortfolioPublicationRepository $publications,
        private readonly PublicationManager $manager,
        private readonly PublicationNormalizer $normalizer,
    ) {
    }

    #[Route('', name: 'unifolio_admin_publications', methods: ['GET'])]
    public function liste(): JsonResponse
    {
        $visibles = array_filter(
            $this->publications->findActivesPourModeration(),
            fn (PortfolioPublication $p) => $this->isGranted(PublicationVoter::MODERER, $p)
        );

        return $this->json(array_map($this->normalizer->pourModeration(...), array_values($visibles)));
    }

    #[Route('/{uuid}/moderer', name: 'unifolio_admin_publications_moderer', requirements: ['uuid' => self::UUID], methods: ['POST'])]
    public function moderer(string $uuid, Request $request): JsonResponse
    {
        $publication = $this->publication($uuid);
        $this->manager->moderer($publication, (string) ($this->corps($request)['motif'] ?? ''));

        return $this->json($this->normalizer->pourModeration($publication));
    }

    #[Route('/{uuid}/retablir', name: 'unifolio_admin_publications_retablir', requirements: ['uuid' => self::UUID], methods: ['POST'])]
    public function retablir(string $uuid): JsonResponse
    {
        $publication = $this->publication($uuid);
        $this->manager->retablir($publication);

        return $this->json($this->normalizer->pourModeration($publication));
    }

    private function publication(string $uuid): PortfolioPublication
    {
        $publication = $this->publications->findOneByUuid($uuid) ?? throw $this->createNotFoundException('Publication introuvable.');
        $this->denyAccessUnlessGranted(PublicationVoter::MODERER, $publication);

        return $publication;
    }

    private function corps(Request $request): array
    {
        try {
            return $request->toArray();
        } catch (\Throwable) {
            throw UnifolioValidationException::un('Requête JSON invalide.');
        }
    }
}
