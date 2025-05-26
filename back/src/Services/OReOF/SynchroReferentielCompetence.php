<?php

namespace App\Services\OReOF;

use App\Entity\Apc\ApcApprentissageCritique;
use App\Entity\Apc\ApcCompetence;
use App\Entity\Apc\ApcNiveau;
use App\Entity\Apc\ApcReferentiel;
use App\Entity\Structure\StructureDiplome;
use App\Repository\Structure\StructureDiplomeRepository;
use App\Services\ApiService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SynchroReferentielCompetence
{
    public function __construct(
        protected StructureDiplomeRepository $structureDiplomeRepository,
        protected HttpClientInterface        $httpClient,
        protected EntityManagerInterface     $entityManager,
        protected ApiService $apiService,
        protected string $apiBaseUrl
    )
    {
    }

    public function syncBut(int $referentielId, StructureDiplome $diplome): void
    {
        $url = $this->apiBaseUrl . '/api/referentiel-competences/' . $referentielId . '/export-json';

        $maquette = $this->apiService->request('GET', $url, [
            'timeout' => 600,
        ]);

        $referentiel = new ApcReferentiel(); //todo: récupérer le référentiel existant ou le créer
        $referentiel->setLibelle($maquette['referentiel']);
        $referentiel->setAnneePublication((new \DateTime('now'))->format('Y'));
        $referentiel->setDepartement($diplome->getDepartement());;
        $referentiel->setTypeDiplome($diplome->getTypeDiplome());

        foreach ($maquette['competences'] as $competence) {
            $comp = new ApcCompetence();
            $comp->setLibelle($competence['libelle']);
            $comp->setNomCourt($competence['nomCourt']);
            $comp->setCouleur('c' . $competence['numero']);
            $comp->setReferentiel($referentiel);
            $comp->setComposantesEssentielles($competence['composantes_essentielles']);
            $comp->setSituationsProfessionnelles($competence['situations_professionnelles']);

            $this->entityManager->persist($comp);

            foreach ($competence['niveaux'] as $niveau) {
                $niv = new ApcNiveau();
                $niv->setLibelle($niveau['libelle']);
                $niv->setOrdre($niveau['ordre']);
                $niv->setCompetence($comp);

                $this->entityManager->persist($niv);

                foreach ($niveau['acs'] as $critique) {
                    $crit = new ApcApprentissageCritique();
                    $crit->setLibelle($critique['libelle']);
                    $crit->setCode($critique['code']);
                    $crit->setNiveau($niv);

                    $this->entityManager->persist($crit);
                }
            }
        }
        $this->entityManager->persist($referentiel);
        $this->entityManager->flush();
    }
}
