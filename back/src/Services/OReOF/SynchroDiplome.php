<?php

namespace App\Services\OReOF;

use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Structure\StructureAnnee;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructurePn;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Structure\StructureUe;
use App\Repository\Apc\ApcCompetenceRepository;
use App\Repository\Structure\StructureDiplomeRepository;
use App\Services\ApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SynchroDiplome
{
    private StructureDiplome|null $diplome;
    public function __construct(
        protected ApcCompetenceRepository $apcCompetenceRepository,
        protected SynchroReferentielCompetence $synchroReferentielCompetence,
        protected StructureDiplomeRepository $structureDiplomeRepository,
        protected HttpClientInterface $httpClient,
        protected EntityManagerInterface $entityManager,
        protected ApiService $apiService,
        protected string $apiBaseUrl
    )
    {
    }

    public function sync(StructureDiplome|int|null $diplome, int $annee)
    {
        $anneeUniversitaire = $this->entityManager->getRepository(StructureAnneeUniversitaire::class)->findOneBy(['annee' => $annee]);

        if ($anneeUniversitaire === null) {
            throw new \Exception('Année universitaire non trouvée');
        }

        if (is_int($diplome)) {
            $diplome = $this->structureDiplomeRepository->find($diplome);
        }


        if ($diplome === null) {
            return;
        }

        $this->diplome = $diplome;
        $this->synchroReferentielCompetence->syncBut($diplome->getCleOreof(), $diplome);

        $url = $this->apiBaseUrl . '/parcours/' . $diplome->getCleOreof() . '/maquette/validee_cfvu/export-json';

        $maquette = $this->apiService->request('GET', $url, [
            'timeout' => 600,
        ]);

        //todo:  mettre à jour le diplôme avec le json


        // regarder si la strucute Pn existe dans le diplôme pour l'année universitaire, sinon créer la structure PN sur laquelle tout s'attache
        $structurePn = $this->entityManager->getRepository(StructurePn::class)->findOneBy(['anneeUniversitaire' =>$anneeUniversitaire, 'diplome' => $diplome]);
        if ($structurePn === null) {
            $structurePn = new StructurePn($diplome);
            $structurePn->setAnneePublication($anneeUniversitaire->getAnnee());
            $structurePn->setLibelle('PN ' . $diplome->getLibelle(). ' ' . $anneeUniversitaire->getLibelle());

            $this->entityManager->persist($structurePn);
        }

        // Parcourir les semestres
        foreach ($maquette['semestres'] as $semestre) {
            // Récupérer ou créer l'année, en sachant que la notion d'année n'existe pas dans ORéOF
            $structureAnnee = $this->getOrCreateAnnee($diplome, $structurePn, $semestre);

            // Créer ou mettre à jour le semestre
            $structureSemestre = $this->getOrCreateSemestre($structureAnnee, $semestre);

            // Parcourir les UE
            foreach ($semestre['ues'] as $ue) {
                // Créer ou mettre à jour l'UE
                $structureUe = $this->getOrCreateUe($structureSemestre, $ue);

                // Parcourir les matières (enseignements)
                if (isset($ue['matieres']) && is_array($ue['matieres'])) {
                    foreach ($ue['matieres'] as $matiere) {
                        // Créer ou mettre à jour la matière
                        $this->getOrCreateMatiere($structureUe, $matiere);
                    }
                }
            }
        }

        // Persister les changements
        $this->entityManager->flush();
    }

    private function getOrCreateAnnee(StructureDiplome $diplome, StructurePn $structurePn, array $semestre): StructureAnnee
    {
        // Rechercher l'année par son libellé ou en créer une nouvelle
        $anneeRepository = $this->entityManager->getRepository(StructureAnnee::class);
        // déterminer selon l'ordre du semestre, l'année correspondante
        $ordre = $semestre['ordre'] % 2 === 0 ? $semestre['ordre'] / 2 : ($semestre['ordre'] + 1) / 2;


        $anneeLibelle = 'Année ' . $ordre;

        $structureAnnee = $anneeRepository->findOneBy(['diplome' => $diplome, 'ordre' => $ordre]);

        if (!$structureAnnee) {
            $structureAnnee = new StructureAnnee();
            $structureAnnee->setLibelle($anneeLibelle);
            $structureAnnee->setOrdre($ordre);
            $structureAnnee->setLibelleLong('...');
            $structureAnnee->setActif(true);
            $structureAnnee->setPn($structurePn);

            $this->entityManager->persist($structureAnnee);
        }

        return $structureAnnee;
    }

    private function getOrCreateSemestre(StructureAnnee $structureAnnee, array $semestre): StructureSemestre
    {
        // Rechercher le semestre par son ordre ou en créer un nouveau
        $semestreRepository = $this->entityManager->getRepository(StructureSemestre::class);
        $structureSemestre = $semestreRepository->findOneBy(['ordreLmd' => $semestre['ordre'], 'annee' => $structureAnnee]);

        if (!$structureSemestre) {
            $structureSemestre = new StructureSemestre();
            $structureSemestre->setOrdreLmd($semestre['ordre']);
            $structureSemestre->setOrdreAnnee($semestre['ordre'] % 2 === 0 ? 2 : 1);
            $structureSemestre->setLibelle('Semestre ' . $semestre['ordre']);
            $structureSemestre->setActif(true);
            $structureSemestre->setNbGroupesCm(1);
            $structureSemestre->setNbGroupesTd(1);
            $structureSemestre->setNbGroupesTp(2);

            if (isset($semestre['code'])) {
                $structureSemestre->setCodeElement($semestre['code']);
            }

            $structureSemestre->setAnnee($structureAnnee);

            $this->entityManager->persist($structureSemestre);
        }

        return $structureSemestre;
    }

    private function getOrCreateUe(StructureSemestre $structureSemestre, array $ue): StructureUe
    {
        // Rechercher l'UE par son code ou en créer une nouvelle
        $ueRepository = $this->entityManager->getRepository(StructureUe::class);
        $structureUe = $ueRepository->findOneBy(['numero' => $ue['ordre'], 'semestre' => $structureSemestre]);

        if (!$structureUe) {
            $structureUe = new StructureUe();
            $structureUe->setLibelle($ue['libelle']);
            $structureUe->setNumero($ue['numero'] ?? 0);
            $structureUe->setNbEcts($ue['ects'] ?? 0);
            $structureUe->setActif(true);
            $structureUe->setBonification(false);
            $structureUe->setCodeElement($ue['code'] ?? '');
            $structureUe->setSemestre($structureSemestre);

            // Si l'UE est liée à une compétence, la récupérer ou la créer
            // si BUT, récupérer la compétence par son code
            if ($this->diplome->getTypeDiplome()?->getSigle() === 'BUT') {
                //récupère la compétence selon le libellé de l'UE et le référentiel
                $competence = $this->apcCompetenceRepository->findOneBy(['nomCourt' => $ue['libelle'], 'referentiel' => $this->diplome->getReferentiel()]);
                $structureUe->setCompetence($competence);
            }

            $this->entityManager->persist($structureUe);
        }

        return $structureUe;
    }

    private function getOrCreateMatiere(StructureUe $structureUe, array $matiere): ScolEnseignement
    {
        // Rechercher la matière par son code ou en créer une nouvelle
        $matiereRepository = $this->entityManager->getRepository(ScolEnseignement::class);
        $scolEnseignement = $matiereRepository->findOneBy(['codeEnseignement' => $matiere['code']]);

        if (!$scolEnseignement) {
            $scolEnseignement = new ScolEnseignement();
            $scolEnseignement->setLibelle($matiere['libelle']);
            $scolEnseignement->setLibelleCourt($matiere['libelle_court'] ?? substr($matiere['libelle'], 0, 25));
            $scolEnseignement->setCodeEnseignement($matiere['code'] ?? '');
            $scolEnseignement->setSuspendu(false);
            $scolEnseignement->setNbNotes($matiere['nb_notes'] ?? 1);
            $scolEnseignement->setMutualisee(false);
            $scolEnseignement->setBonification(false);

            // Définir le type d'enseignement
            if (isset($matiere['type']) && $matiere['type'] === 'SAE') {
                $scolEnseignement->setType(\App\Enum\TypeEnseignementEnum::TYPE_SAE);
            } else {
                $scolEnseignement->setType(\App\Enum\TypeEnseignementEnum::TYPE_RESSOURCE);
            }

            // Définir les heures
            $heures = [
                'CM' => ['PN' => $matiere['heures_cm'] ?? 0, 'IUT' => $matiere['heures_cm'] ?? 0],
                'TD' => ['PN' => $matiere['heures_td'] ?? 0, 'IUT' => $matiere['heures_td'] ?? 0],
                'TP' => ['PN' => $matiere['heures_tp'] ?? 0, 'IUT' => $matiere['heures_tp'] ?? 0],
                'Projet' => ['PN' => $matiere['heures_projet'] ?? 0, 'IUT' => $matiere['heures_projet'] ?? 0],
            ];
            $scolEnseignement->setHeures($heures);

            // Définir les autres champs si disponibles
            if (isset($matiere['pre_requis'])) {
                $scolEnseignement->setPreRequis($matiere['pre_requis']);
            }

            if (isset($matiere['objectif'])) {
                $scolEnseignement->setObjectif($matiere['objectif']);
            }

            if (isset($matiere['description'])) {
                $scolEnseignement->setDescription($matiere['description']);
            }

            if (isset($matiere['mots_cles'])) {
                $scolEnseignement->setMotsCles($matiere['mots_cles']);
            }

            $this->entityManager->persist($scolEnseignement);

            // Créer la relation entre l'enseignement et l'UE
            $scolEnseignementUe = new \App\Entity\Scolarite\ScolEnseignementUe($scolEnseignement, $structureUe);
            $scolEnseignementUe->setEcts($matiere['ects'] ?? 0);
            $scolEnseignementUe->setCoefficient($matiere['coefficient'] ?? 1);

            $this->entityManager->persist($scolEnseignementUe);
        }

        return $scolEnseignement;
    }
}
