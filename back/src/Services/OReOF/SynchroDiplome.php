<?php

namespace App\Services\OReOF;

use App\Entity\Structure\StructureDiplome;
use App\Repository\Structure\StructureDiplomeRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SynchroDiplome
{
    public function __construct(
        protected StructureDiplomeRepository $structureDiplomeRepository,
        protected HttpClientInterface $httpClient
    )
    {
    }

    public function sync(StructureDiplome|int|null $diplome)
    {
        if (is_int($diplome)) {
            $diplome = $this->structureDiplomeRepository->find($diplome);
        }

        if ($diplome === null) {
            return;
        }

        // Logique pour la synchronisation d'un diplôme

        // récupérer les données via HttpClient sur l'URL de l'API ORéOF
        $response = $this->httpClient->request('GET', 'https://oreof.univ-reims.fr/parcours/'.$diplome->getId().'/maquette/export-json', [
            'timeout' => 600,
        ]);

        $maquette = json_decode($response->getContent(), true);
        // Check for JSON decoding errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Error decoding JSON response: ' . json_last_error_msg());
        }

        // parcourir les semestres, la base "année" ne change pas. Les semestres sont accrochés au PN ?
    }
}
