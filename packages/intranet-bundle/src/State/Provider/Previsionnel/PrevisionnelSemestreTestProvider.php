<?php

namespace IntranetBundle\State\Provider\Previsionnel;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;

class PrevisionnelSemestreTestProvider implements ProviderInterface
{

    public function __construct(
        private CollectionProvider $collectionProvider,
        private PrevisionnelSemestreProvider $semestreProvider
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

            $output = [
                'prevSem' => [],
                'heures' => [],
                'groupes' => [],
            ];
            foreach ($data as $item) {
                if ($item->getPersonnel() !== null && $item->getEnseignement() !== null) {
                    if (!array_key_exists($item->getEnseignement()->getId(), $output['heures'])) {
                        $output['heures'][$item->getEnseignement()->getId()] = [];
                    }
                    if (!array_key_exists($item->getPersonnel()->getId(), $output['heures'][$item->getEnseignement()->getId()])) {
                        $output['heures'][$item->getEnseignement()->getId()][$item->getPersonnel()->getId()] = [];
                    }

                    if (!array_key_exists($item->getEnseignement()->getId(), $output['groupes'])) {
                        $output['groupes'][$item->getEnseignement()->getId()] = [];
                    }
                    if (!array_key_exists($item->getPersonnel()->getId(), $output['groupes'][$item->getEnseignement()->getId()])) {
                        $output['groupes'][$item->getEnseignement()->getId()][$item->getPersonnel()->getId()] = [];
                    }
                    $output['prevSem'][] = $this->semestreProvider->formToDto($item);
                    $enseignementId = $item->getEnseignement()->getId();
                    $personnelId = $item->getPersonnel()->getId();
                    foreach (['CM', 'TD', 'TP', 'Projet'] as $type) {
                        $output['heures'][$enseignementId][$personnelId][$type] =
                            ($output['heures'][$enseignementId][$personnelId][$type] ?? 0) + ($item->getHeures()[$type] ?? 0);
                        $output['groupes'][$enseignementId][$personnelId][$type] =
                            ($output['groupes'][$enseignementId][$personnelId][$type] ?? 0) + ($item->getGroupes()[$type] ?? 0);
                    }
                }
            }

            return $output;

        } else {
            return [];
        }
    }
}
