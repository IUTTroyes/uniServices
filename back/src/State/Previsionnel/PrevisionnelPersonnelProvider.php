<?php

namespace App\State\Previsionnel;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiDto\Previsionnel\PrevisionnelEnseignementDto;

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

            $output = [];

            foreach ($data as $item) {

                $output['previ'][] = $this->toDto($item);
            }

            return $output;
        } else {
            $data = $this->itemProvider->provide($operation, $uriVariables, $context);
        }

        return $this->toDto($data);
    }

    public function toDto($item)
    {
        $prevMatiere = new PrevisionnelEnseignementDto();
        $prevMatiere->setLibelle($item->getEnseignement()->getLibelle());
        $prevMatiere->setPersonnel($item->getPersonnel());
        $prevMatiere->setHeures(
            [
                'CM' => 0,
                'TD' => 0,
                'TP' => 0,
            ]
        );

        return $prevMatiere;
    }
}
