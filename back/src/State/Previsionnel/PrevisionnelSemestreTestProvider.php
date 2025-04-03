<?php

namespace App\State\Previsionnel;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiDto\Previsionnel\PrevisionnelSemestreDto;
use App\Repository\Structure\StructureSemestreRepository;

class PrevisionnelSemestreTestProvider implements ProviderInterface
{

    public function __construct(
        private CollectionProvider $collectionProvider,
        private ItemProvider $itemProvider,
        private StructureSemestreRepository $semestreRepository
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof GetCollection) {
            $data = $this->collectionProvider->provide($operation, $uriVariables, $context);

            if (empty($data)) {
                return [];
            }

            $prevSem = [];
            $output = [
                'prevSem' => [],
                'heures' => [],
            ];
            foreach ($data as $item) {
                if ($item->getPersonnel() !== null && $item->getEnseignement() !== null) {
                    if (!array_key_exists($item->getEnseignement()->getId(), $output['heures'])) {
                        $output['heures'][$item->getEnseignement()->getId()] = [];
                    }
                    if (!array_key_exists($item->getPersonnel()->getId(), $output['heures'][$item->getEnseignement()->getId()])) {
                        $output['heures'][$item->getEnseignement()->getId()][$item->getPersonnel()->getId()] = [];
                    }
                    $output['prevSem'][] = $this->formToDto($item);
                    $output['heures'][$item->getEnseignement()->getId()][$item->getPersonnel()->getId()] = $item->getHeures();
                }
            }

            return $output;

        } else {
            return [];
        }
    }

    public function formToDto($item): PrevisionnelSemestreDto
    {
        $prevSem = new PrevisionnelSemestreDto();
        $prevSem->setId($item->getId());
        $prevSem->setIdEnseignement($item->getEnseignement()->getId());
        $prevSem->setCodeEnseignement($item->getEnseignement()->getCodeEnseignement());
        $prevSem->setLibelleEnseignement($item->getEnseignement()->getDisplay());
        $prevSem->setTypeEnseignement($item->getEnseignement()->getType());
        $prevSem->setIdPersonnel($item->getPersonnel()->getId());
        $prevSem->setPersonnels([$item->getPersonnel()]);
        $prevSem->setIntervenant($item->getPersonnel()->getDisplay());
        $prevSem->setHeures(
            [
                'CM' => [
                    'NbHrGrp' => round($item->getGroupes()['CM'] !== 0 ? $item->getHeures()['CM']/$item->getGroupes()['CM'] : $item->getHeures()['CM'], 1),
                    'NbGrp' => $item->getGroupes()['CM'],
                    'NbSeanceGrp' => round((($item->getGroupes()['CM'] !== 0 ? $item->getHeures()['CM']/$item->getGroupes()['CM'] : $item->getHeures()['CM']) / $item::DUREE_SEANCE) * $item->getGroupes()['CM'], 0),
                ],
                'TD' => [
                    'NbHrGrp' => round($item->getGroupes()['TD'] !== 0 ? $item->getHeures()['TD']/$item->getGroupes()['TD'] : $item->getHeures()['TD'], 1),
                    'NbGrp' => $item->getGroupes()['TD'],
                    'NbSeanceGrp' => round((($item->getGroupes()['TD'] !== 0 ? $item->getHeures()['TD']/$item->getGroupes()['TD'] : $item->getHeures()['TD']) / $item::DUREE_SEANCE) * $item->getGroupes()['TD'], 0),
                ],
                'TP' => [
                    'NbHrGrp' => round($item->getGroupes()['TP'] !== 0 ? $item->getHeures()['TP']/$item->getGroupes()['TP'] : $item->getHeures()['TP'], 1),
                    'NbGrp' => $item->getGroupes()['TP'],
                    'NbSeanceGrp' => round((($item->getGroupes()['TP'] !== 0 ? $item->getHeures()['TP']/$item->getGroupes()['TP'] : $item->getHeures()['TP']) / $item::DUREE_SEANCE) * $item->getGroupes()['TP'], 0),
                ],
                'Projet' => [
                    'NbHrGrp' => $item->getHeures()['Projet'],
                    'NbGrp' => $item->getGroupes()['Projet'],
                    'NbSeanceGrp' => ($item->getHeures()['Projet'] / $item::DUREE_SEANCE),
                ],
            ]
        );
        $prevSem->setGroupes($item->getGroupes());
        return $prevSem;
    }
}
