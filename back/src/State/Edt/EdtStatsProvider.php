<?php

namespace App\State\Edt;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiDto\Edt\EdtStatsDto;

class EdtStatsProvider implements ProviderInterface
{

    public function __construct(
        private CollectionProvider $collectionProvider,
        private ItemProvider $itemProvider,
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

            // calculer la somme des durées (en heures)
            $totalSeconds = 0;
            foreach ($data as $item) {
                // supposer que $item a getDebut() et getFin() retournant \DateTimeInterface ou null
                $debut = method_exists($item, 'getDebut') ? $item->getDebut() : null;
                $fin = method_exists($item, 'getFin') ? $item->getFin() : null;
                if ($debut instanceof \DateTimeInterface && $fin instanceof \DateTimeInterface) {
                    $totalSeconds += $fin->getTimestamp() - $debut->getTimestamp();
                }
            }
            $totalHeures = $totalSeconds > 0 ? round($totalSeconds / 3600, 2) : 0.0;

            $dto = new EdtStatsDto();
            $dto->setTotalHeures($totalHeures);

            // retourner un tableau de DTOs pour une opération de collection
            return [$dto];
        } else {
            $data = $this->itemProvider->provide($operation, $uriVariables, $context);
            if (null === $data) {
                return null;
            }
            // pour un item, convertir l'entité en DTO si besoin
            $dto = new EdtStatsDto();
            // mapping minimal : exemple pour totalHours calculé depuis l'item unique
            $debut = method_exists($data, 'getDebut') ? $data->getDebut() : null;
            $fin = method_exists($data, 'getFin') ? $data->getFin() : null;
            if ($debut instanceof \DateTimeInterface && $fin instanceof \DateTimeInterface) {
                $hours = round(($fin->getTimestamp() - $debut->getTimestamp()) / 3600, 2);
            } else {
                $hours = 0.0;
            }
            $dto->setTotalHeures($hours);
            return $dto;
        }
    }

    public function syntheseToDto($item): EdtStatsDto
    {
        return $item;
    }

}
