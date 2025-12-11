<?php

namespace App\State\Edt;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use DateTime;
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
                // Renvoie un DTO vide
                return new EdtStatsDto();
            }

            $dto = new EdtStatsDto();

            $totals = [
                'totalHeures' => 0.0,
            ];

            $byType = [];

            foreach ($data as $item) {
                $start = $item->getDebut();
                $end = $item->getFin();

                // $start et $end sont déjà des \DateTimeInterface si l'entité est bien configurée
                if (!$start || !$end) continue;

                $interval = $start->diff($end);
                $duration = $interval->h + ($interval->days * 24) + ($interval->i / 60); // durée en heures

                $totals['totalHeures'] += $duration;

                $type = (string) $item->getType();
                if ($type === '') $type = 'UNKNOWN';

                if (!isset($byType[$type])) $byType[$type] = 0.0;
                $byType[$type] += $duration;
            }

            $heuresParType = [
                'CM' => 0,
                'TD' => 0,
                'TP' => 0,
            ];
            foreach ($byType as $type => $heures) {
                $heuresParType[$type] = (float) $heures;
            }

            $total = (float) $totals['totalHeures'];

            // Construire la répartition comme tableau [{type, heures, pourcentage}, ...]
            $repartition = [];
            foreach ($heuresParType as $type => $heures) {
                $pourcentage = $total > 0 ? round(($heures / $total) * 100, 1) : 0.0;
                $repartition[] = ['type' => $type, 'heures' => $heures, 'pourcentage' => $pourcentage];
            }

            $dto->setTotalHeures($total);
            $dto->setHeuresParType($heuresParType);
            $dto->setRepartition($repartition);

            return $dto;
        }

        // Cas item: on effectue un simple pass-through également
        return $this->itemProvider->provide($operation, $uriVariables, $context);
    }
}
