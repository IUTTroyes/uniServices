<?php

namespace App\State\Edt;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\Previsionnel\PrevisionnelRepository;
use DateTime;
use App\ApiDto\Edt\EdtStatsDto;

class EdtStatsProvider implements ProviderInterface
{

    public function __construct(
        private CollectionProvider $collectionProvider,
        private ItemProvider $itemProvider,
        private PrevisionnelRepository $previsionnelRepository,
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
            $bySemestre = [];
            $byEnseignement = [];
            $byEnseignant = [];
            $stats_export = [];

            foreach ($data as $item) {
                $start = $item->getDebut();
                $end = $item->getFin();

                if (!$start || !$end) continue;

                $interval = $start->diff($end);
                $duration = $interval->h + ($interval->days * 24) + ($interval->i / 60); // durée en heures

                $totals['totalHeures'] += $duration;

                $type = (string) $item->getType();
                if ($type === '') $type = 'Type inconnu';

                if (!isset($byType[$type])) $byType[$type] = 0.0;
                $byType[$type] += $duration;

                $semestre = (string) $item->getSemestre()->getLibelle();
                if (!isset($bySemestre[$semestre])) $bySemestre[$semestre] = 0.0;
                $bySemestre[$semestre] += $duration;

                $enseignement = $item->getEnseignement();
                $enseignementLibelle = $enseignement?->getLibelle();
                $enseignementId = $enseignement?->getId();
                if ($enseignementLibelle) {
                    if (!isset($byEnseignement[$enseignementLibelle])) {
                        $byEnseignement[$enseignementLibelle] = [
                            'id' => $enseignementId,
                            'heures' => 0.0,
                        ];
                    }
                    // Si plusieurs événements partagent le même libellé mais que l'id est manquant, tenter de le définir
                    if (!isset($byEnseignement[$enseignementLibelle]['id']) && $enseignementId) {
                        $byEnseignement[$enseignementLibelle]['id'] = $enseignementId;
                    }
                    $byEnseignement[$enseignementLibelle]['heures'] += $duration;
                }

                $enseignantDisplay = $item->getPersonnel()?->getDisplay();
                if ($enseignantDisplay) {
                    if (!isset($byEnseignant[$enseignantDisplay])) {
                        $byEnseignant[$enseignantDisplay] = 0.0;
                    }
                    $byEnseignant[$enseignantDisplay] += $duration;
                }
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
            $repartitionTypes = [];
            foreach ($heuresParType as $type => $heures) {
                $pourcentage = $total > 0 ? round(($heures / $total) * 100, 1) : 0.0;
                $repartitionTypes[] = ['type' => $type, 'heures' => $heures, 'pourcentage' => $pourcentage];
            }

            $heuresParSemestre = [];
            foreach ($bySemestre as $semestre => $heures) {
                $heuresParSemestre[$semestre] = (float) $heures;
            }

            $repartitionSemestres = [];
            foreach ($heuresParSemestre as $semestre => $heures) {
                $pourcentage = $total > 0 ? round(($heures / $total) * 100, 1) : 0.0;
                $repartitionSemestres[] = ['semestre' => $semestre, 'heures' => $heures, 'pourcentage' => $pourcentage];
            }

            $heuresParEnseignements = [];
            foreach ($byEnseignement as $enseignementLibelle => $infos) {
                // $infos should be ['id' => ?, 'heures' => float]
                $heuresParEnseignements[$enseignementLibelle] = [
                    'id' => $infos['id'] ?? null,
                    'heures' => (float) ($infos['heures'] ?? 0.0),
                ];
            }

            $heuresParEnseignants = [];
            foreach ($byEnseignant as $enseignantId => $heures) {
                $heuresParEnseignants[$enseignantId] = (float) $heures;
            }

            $dto->setTotalHeures($total);
            $dto->setHeuresParType($heuresParType);
            $dto->setRepartitionTypes($repartitionTypes);
            $dto->setRepartitionSemestres($repartitionSemestres);
            $dto->setRepartitionEnseignements($heuresParEnseignements);
            $dto->setRepartitionEnseignants($heuresParEnseignants);

            return $dto;
        }

        // Cas item: on effectue un simple pass-through également
        return $this->itemProvider->provide($operation, $uriVariables, $context);
    }
}
