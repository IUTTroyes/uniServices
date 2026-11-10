<?php

namespace App\Controller;

use App\Repository\EtablissementRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class PublicLoginController
{
    #[Route('/api/public/login-branding', name: 'api_public_login_branding', methods: ['GET'])]
    public function __invoke(EtablissementRepository $repository): JsonResponse
    {
        // Liste explicite : ne jamais sérialiser les paramètres privés de l'établissement.
        return new JsonResponse([
            'logo_name' => $repository->findSingleEtablissement()?->getLogoName(),
        ]);
    }
}
