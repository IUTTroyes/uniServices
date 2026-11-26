<?php

namespace App\Controller;

use App\Repository\EtablissementRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[\App\AnneeUniversitaire\ContexteAnnuel('independant', 'Identité, paramétrage partagé ou données sans année universitaire.')]
final class PublicLoginController
{
    #[Route('/api/public/login-branding', name: 'api_public_login_branding', methods: ['GET'])]
    public function __invoke(EtablissementRepository $repository): JsonResponse
    {
        // Liste explicite : ne jamais sérialiser les paramètres privés de l'établissement.
        $etablissement = $repository->findSingleEtablissement();

        return new JsonResponse([
            'logo_name' => $etablissement?->getLogoName(),
            'url_mot_de_passe_cas' => $etablissement?->getUrlMotDePasseCas(),
        ]);
    }
}
