<?php

namespace App\State\Previsionnel;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiDto\Previsionnel\PrevisionnelPersonnelDto;

class PrevisionnelPersonnelProvider implements ProviderInterface
{

    public function __construct(
        private CollectionProvider $collectionProvider,
        private ItemProvider $itemProvider
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

            $output = [];

            foreach ($data as $item) {

                $output['previ'][] = $this->toDto($item);
            }

            $output['test'][] = 0;

            return $output;
        } else {
            $data = $this->itemProvider->provide($operation, $uriVariables, $context);
        }

        return $this->toDto($data);
    }

    public function toDto($item)
    {
        $prevEnseignant = new PrevisionnelPersonnelDto();
        $prevEnseignant->setId($item->getId());
        $prevEnseignant->setIdEnseignement($item->getEnseignement()->getId());
        $prevEnseignant->setLibelle($item->getEnseignement()->getLibelle());
        $prevEnseignant->setLibelleEnseignement($item->getEnseignement()->getDisplay());
        $prevEnseignant->setIdPersonnel($item->getPersonnel()->getId());
        $prevEnseignant->setPersonnel($item->getPersonnel());
        $prevEnseignant->setHeures(
            [
                'CM' => $item->getHeures()['CM'] ?? 0,
                'TD' => $item->getHeures()['TD'] ?? 0,
                'TP' => $item->getHeures()['TP'] ?? 0,
                'Projet' => $item->getHeures()['Projet'] ?? 0,
            ]
        );
        $prevEnseignant->setGroupes(
            [
                'CM' => $item->getGroupes()['CM'] ?? 0,
                'TD' => $item->getGroupes()['TD'] ?? 0,
                'TP' => $item->getGroupes()['TP'] ?? 0,
                'Projet' => $item->getGroupes()['Projet'] ?? 0,
            ]
        );

        return $prevEnseignant;
    }
}
