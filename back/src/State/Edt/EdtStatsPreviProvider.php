<?php

namespace App\State\Edt;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiDto\Edt\EdtStatsPreviDto;
use App\Repository\Previsionnel\PrevisionnelRepository;
use DateTime;
use App\ApiDto\Edt\EdtStatsDto;

class EdtStatsPreviProvider implements ProviderInterface
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
                return new EdtStatsPreviDto();
            }

            $dto = new EdtStatsPreviDto();

            $byEnseignement = [];
            $byEnseignant = [];
            // Heures EDT par enseignement et par type (CM/TD/TP/Projet)
            $byEnseignementType = [];

            foreach ($data as $item) {
                $start = $item->getDebut();
                $end = $item->getFin();

                if (!$start || !$end) continue;

                $interval = $start->diff($end);
                $duration = $interval->h + ($interval->days * 24) + ($interval->i / 60); // durée en heures

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

                    // Par type (CM/TD/TP/Projet)
                    $type = (string) $item->getType();
                    if ($type === '') { $type = 'UNKNOWN'; }
                    if (!isset($byEnseignementType[$enseignementLibelle])) {
                        $byEnseignementType[$enseignementLibelle] = [];
                    }
                    if (!isset($byEnseignementType[$enseignementLibelle][$type])) {
                        $byEnseignementType[$enseignementLibelle][$type] = 0.0;
                    }
                    $byEnseignementType[$enseignementLibelle][$type] += $duration;
                }

                $enseignantDisplay = $item->getPersonnel()?->getDisplay();
                if ($enseignantDisplay) {
                    if (!isset($byEnseignant[$enseignantDisplay])) {
                        $byEnseignant[$enseignantDisplay] = 0.0;
                    }
                    $byEnseignant[$enseignantDisplay] += $duration;
                }
            }

            // Construire les lignes de comparatif par type de groupe
            $rows = [];
            $typesList = ['CM', 'TD', 'TP', 'Projet'];

            foreach ($byEnseignement as $enseignementLibelle => $infos) {
                $enseignementId = $infos['id'] ?? null;

                // Récupère les prévisionnels liés à cet enseignement
                $previParType = [
                    'CM' => 0.0,
                    'TD' => 0.0,
                    'TP' => 0.0,
                    'Projet' => 0.0,
                ];
                $previsionnels = $enseignementId ? $this->previsionnelRepository->findBy(['enseignement' => $enseignementId]) : [];
                if ($previsionnels) {
                    foreach ($previsionnels as $previsionnel) {
                        $heures = (array) $previsionnel->getHeures();
                        $groupes = $previsionnel->getGroupes();
                        foreach (['CM','TD','TP','Projet'] as $t) {
                            $h = (float) ($heures[$t] ?? 0.0);
                            // Si le tableau des groupes est défini et contient la clé, on l'utilise tel quel (peut valoir 0)
                            // Sinon, fallback à 1 groupe pour conserver l'ancien comportement
                            $g = (is_array($groupes) && array_key_exists($t, $groupes)) ? (int) $groupes[$t] : 1;
                            $previParType[$t] += $h * $g;
                        }
                    }
                }

                $edtParType = $byEnseignementType[$enseignementLibelle] ?? [];

                foreach ($typesList as $type) {
                    $edt = (float) ($edtParType[$type] ?? 0.0);
                    $previ = (float) ($previParType[$type] ?? 0.0);
                    $diff = $previ - $edt;

                    // Inclure uniquement les lignes pertinentes
                    if ($previ > 0 || $edt > 0) {
                        $rows[] = [
                            'id' => $enseignementId,
                            'enseignement' => $enseignementLibelle,
                            'type' => $type,
                            'heures_previsionnel' => $previ,
                            'heures_edt' => $edt,
                            'heures_diff' => $diff,
                        ];
                    }
                }
            }

            // Heures par enseignant (inchangé)
            $heuresParEnseignants = [];
            foreach ($byEnseignant as $enseignantId => $heures) {
                $heuresParEnseignants[$enseignantId] = (float) $heures;
            }

            $dto->setRepartitionEnseignements($rows);
            $dto->setRepartitionEnseignants($heuresParEnseignants);

            return $dto;
        }

        // Cas item: on effectue un simple pass-through également
        return $this->itemProvider->provide($operation, $uriVariables, $context);
    }
}
