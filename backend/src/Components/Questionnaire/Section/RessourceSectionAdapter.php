<?php
/*
 * Copyright (c) 2023. | David Annebicque | IUT de Troyes  - All Rights Reserved
 * @file /Users/davidannebicque/Sites/intranetV3/src/Components/Questionnaire/Section/RessourceSectionAdapter.php
 * @author davidannebicque
 * @project intranetV3
 * @lastUpdate 03/08/2023 10:23
 */

namespace App\Components\Questionnaire\Section;

use App\Entity\ApcRessource;
use App\Entity\Semestre;
use App\Repository\ApcRessourceRepository;

class RessourceSectionAdapter extends AbstractSectionAdapter
{
    final public const ENTITY = ApcRessource::class;
    final public const FIELD_LIBELLE = 'libelle';
    final public const FIELD_CODE = 'codeMatiere';
    final public const LABEL = 'ressources';

    public function __construct(
        protected ApcRessourceRepository $apcRessourceRepository
    ) {
    }

    public function getData(mixed $id): ?array
    {
        $matiere = $this->apcRessourceRepository->find($id);
        if (null !== $matiere) {
            return [
                'libelle' => $matiere->getLibelle(),
                'code' => $matiere->getCodeElement(),
                'personnel' => '',
                'id' => $id,
                'affichage' => $matiere->getCodeElement() . ' | ' . $matiere->getLibelle(),
            ];
        }

        return null;
    }

    public function getAllDataSemestre(Semestre $semestre, array $selectionnes): array
    {
        $data = [];
        $previs = $this->apcRessourceRepository->findByReferentielOrdreSemestre($semestre->getDiplome()?->getReferentiel(),
            $semestre->getOrdreLmd());
        foreach ($previs as $previ) {
            $data[] = [
                'libelle' => $previ->getLibelle(),
                'code' => $previ->getCodeElement(),
                'personnel' => '',
                'id' => $previ->getId(),
                'checked' => in_array($previ->getId(), $selectionnes),
                'affichage' => $previ->getCodeElement() . ' | ' . $previ->getLibelle(),
            ];
        }
        return $data;
    }
}
