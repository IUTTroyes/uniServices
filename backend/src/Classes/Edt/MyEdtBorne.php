<?php
/*
 * Copyright (c) 2024. | David Annebicque | IUT de Troyes  - All Rights Reserved
 * @file /Users/davidannebicque/Sites/intranetV3/src/Classes/Edt/MyEdtBorne.php
 * @author davidannebicque
 * @project intranetV3
 * @lastUpdate 23/02/2024 21:40
 */

namespace App\Classes\Edt;

use App\Classes\Matieres\TypeMatiereManager;
use App\Entity\AnneeUniversitaire;
use App\Entity\Semestre;
use App\Exception\SemestreNotFoundException;
use App\Repository\CalendrierRepository;
use App\Repository\EdtPlanningRepository;
use App\Repository\GroupeRepository;
use App\Repository\SemestreRepository;

class MyEdtBorne
{
    public array $data = []; // todo: passer par EvenementCollection pour gérer tous les cas??

    /**
     * MyEdtBorne constructor.
     */
    public function __construct(private readonly CalendrierRepository $calendrierRepository, private readonly GroupeRepository $groupeRepository, private readonly EdtManager $edtManager, private readonly SemestreRepository $semestreRepository, private readonly EdtPlanningRepository $edtPlanningRepository)
    {
    }

    public function init(): void
    {
        $this->data['semaine'] = (int) date('W');
        $this->data['njour'] = (int) date('d');
        $this->data['jsem'] = (int) date('N');
    }

    public function getData(): array
    {
        return $this->data;
    }

    /** @deprecated */
    public function calculSemestre(
        Semestre $semestre1,
        Semestre $semestre2,
        AnneeUniversitaire $anneeUniversitaire,
        TypeMatiereManager $typeMatiereManager
    ): void {
        $semaine = $this->calendrierRepository->findOneBy([
            'semaineReelle' => $this->data['semaine'],
            'anneeUniversitaire' => $anneeUniversitaire->getId(),
        ]);

        $this->data['semestre1'] = $semestre1;
        $this->data['semestre2'] = $semestre2;
        if (null !== $semaine) {
            $this->data['p1']['planning'] = $this->edtPlanningRepository->recupereEDTBornes($semaine->getSemaineFormation(),
                $semestre1, $this->data['jsem'], $typeMatiereManager->findBySemestreArray($semestre1));
            $this->data['p2']['planning'] = $this->edtPlanningRepository->recupereEDTBornes($semaine->getSemaineFormation(),
                $semestre2, $this->data['jsem'], $typeMatiereManager->findBySemestreArray($semestre2));

            $this->data['p1']['groupes'] = $this->groupeRepository->findAllGroupes($semestre1);
            $this->data['p2']['groupes'] = $this->groupeRepository->findAllGroupes($semestre2);
            $this->data['jours'] = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
            $this->data['j1'] = $this->data['jours'][$this->data['jsem']].' '.date('d/m/Y',
                    mktime(12, 30, 00, (int) date('n'), (int) $this->data['njour'], (int) date('Y')));
        }
    }

    /**
     * @throws SemestreNotFoundException
     */
    public function getAffichageBorneJourSemestre(
        mixed $intSemestre, TypeMatiereManager $typeMatiereManager): array
    {
        $semestre = $this->semestreRepository->find($intSemestre);

        if (null === $semestre) {
            throw new SemestreNotFoundException();
        }

        $anneeUniversitaire = $semestre->getAnneeUniversitaire();

        if (null === $anneeUniversitaire) {
            throw new SemestreNotFoundException();
        }

        $semaine = $this->calendrierRepository->findOneBy([
            'semaineReelle' => $this->data['semaine'],
            'anneeUniversitaire' => $anneeUniversitaire->getId(),
        ]);

        $groupes = $this->groupeRepository->findByDiplomeAndOrdreSemestre($semestre->getDiplome(), $semestre->getOrdreLmd());

        $this->data['semestre'] = $semestre;
        $matieres = $typeMatiereManager->findByReferentielOrdreSemestre($semestre, $semestre->getDiplome()->getReferentiel());
        $tMatieres = [];
        foreach ($matieres as $matiere) {
            $tMatieres[$matiere->getTypeIdMatiere()] = $matiere;
        }
        if (null !== $semaine) {
            $planning = $this->edtManager->recupereEDTBornes($semaine->getSemaineFormation(),
                $semestre, $this->data['jsem'], $tMatieres, $groupes, $anneeUniversitaire);
            $tab = [];
            foreach ($planning->getEvents() as $pl) {
                if ($pl->ordreGroupe === 41) {
                    $tab[1][$pl->gridStart] = $pl;
                } else {
                    $tab[$pl->ordreGroupe][$pl->gridStart] = $pl;
                }

            }
            $this->data['planning'] = $tab;
            $this->data['p1']['groupes'] = $this->groupeRepository->findAllGroupes($semestre);
            $this->data['jours'] = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
            $this->data['j1'] = $this->data['jours'][$this->data['jsem']].' '.date('d/m/Y',
                    mktime(12, 30, 00, (int) date('n'), (int) $this->data['njour'], (int) date('Y')));
        }

        return $this->data;
    }
}
