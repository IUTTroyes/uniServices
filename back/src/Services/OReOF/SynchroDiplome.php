<?php

namespace App\Services\OReOF;

use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructureSemestre;
use App\Repository\Structure\StructureDiplomeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SynchroDiplome
{
    public function __construct(
        protected StructureDiplomeRepository $structureDiplomeRepository,
        protected HttpClientInterface $httpClient,
        protected EntityManagerInterface $entityManager
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
        $response = $this->httpClient->request('GET', 'https://oreof.univ-reims.fr/parcours/'.$diplome->getCleOreof().'/maquette/export-json', [
            'timeout' => 600,
        ]);

        $maquette = json_decode($response->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Error decoding JSON response: ' . json_last_error_msg());
        }

        // parcourir les semestres, la base "année" ne change pas. Les semestres sont accrochés au PN ?

        foreach ($maquette['semestres'] as $semestre) {
            // Logique pour la synchronisation d'un semestre
            $structureSemestre = new StructureSemestre();
            $structureSemestre->setAnnee($maquette['annee']);
            $structureSemestre->setOrdreLmd($semestre['ordre']);
            $structureSemestre->setOrdreAnnee($semestre['ordre'] % 2 === 0 ? 2 : 1);
            $structureSemestre->setLibelle('Semestre ' . $semestre['ordre']);
            $structureSemestre->setAnnee();
            $structureSemestre->setCodeElement();

            $this->entityManager->persist($structureSemestre);

            // parcourir les UE
            foreach ($semestre['ues'] as $ue) {
                // Logique pour la synchronisation d'une UE
            }
        }
    }
}
