<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/address')]
class AddressController extends AbstractController
{
    // URLs officielles Géoplateforme (data.geopf.fr)
    // Documentation: https://geocodage.ign.fr/
    private const GEOCODAGE_SEARCH_URL = 'https://data.geopf.fr/geocodage/search';
    private const GEOCODAGE_REVERSE_URL = 'https://data.geopf.fr/geocodage/reverse';

    public function __construct(private HttpClientInterface $httpClient)
    {
    }

    /**
     * Géocodage direct : recherche des adresses via Géoplateforme (proxy pour contourner le CORS)
     * Utilise le service de géocodage officiel Géoplateforme
     *
     * Paramètres supportés par l'API :
     * - q (requis): texte de recherche
     * - limit (optionnel): nombre de résultats (non supporté, peut être ignoré)
     */
    #[Route('/search', name: 'api_address_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q');

        // Validation
        if (!$query || strlen(trim($query)) < 3) {
            return new JsonResponse(['features' => []], 400);
        }

        try {
            // Envoi seulement du paramètre 'q' qui est supporté par l'API
            $response = $this->httpClient->request('GET', self::GEOCODAGE_SEARCH_URL, [
                'query' => [
                    'q' => $query,
                ],
                'headers' => [
                    'User-Agent' => 'uniServices/1.0',
                ],
                'timeout' => 5,
            ]);

            $data = $response->toArray();

            return new JsonResponse($data);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Erreur lors de la recherche d\'adresses', 'message' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Géocodage inversé : convertir des coordonnées GPS en adresse
     * Utilise le service de géocodage inverse officiel Géoplateforme
     */
    #[Route('/reverse', name: 'api_address_reverse', methods: ['GET'])]
    public function reverse(Request $request): JsonResponse
    {
        $lat = $request->query->get('lat');
        $lon = $request->query->get('lon');

        // Validation
        if (!$lat || !$lon) {
            return new JsonResponse(['error' => 'Les paramètres lat et lon sont requis'], 400);
        }

        try {
            $response = $this->httpClient->request('GET', self::GEOCODAGE_REVERSE_URL, [
                'query' => [
                    'lon' => $lon,
                    'lat' => $lat,
                ],
                'headers' => [
                    'User-Agent' => 'uniServices/1.0',
                ],
                'timeout' => 5,
            ]);

            $data = $response->toArray();

            return new JsonResponse($data);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Erreur lors du géocodage inversé', 'message' => $e->getMessage()],
                500
            );
        }
    }
}


