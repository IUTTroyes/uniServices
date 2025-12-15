<?php

namespace App\State\Previsionnel;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiDto\Previsionnel\PreviStatsEdtDto;
use App\Repository\Previsionnel\PrevisionnelRepository;
use App\Repository\Edt\EdtEventRepository;

class PreviStatsEdtProvider implements ProviderInterface
{

    public function __construct(
        private CollectionProvider $collectionProvider,
        private ItemProvider $itemProvider,
        private PrevisionnelRepository $previsionnelRepository,
        private EdtEventRepository $edtEventRepository,
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof GetCollection) {
            $data = $this->collectionProvider->provide($operation, $uriVariables, $context);

            $dto = new PreviStatsEdtDto();
            if (empty($data)) {
                return $dto;
            }

            // Détermination dynamique des types de groupes à partir des lignes de prévisionnel
            $typesSet = [];
            foreach ($data as $previ) {
                $heures = (array) $previ->getHeures();
                $groupes = (array) $previ->getGroupes();
                $keys = array_unique(array_merge(array_keys($heures), array_keys($groupes)));
                foreach ($keys as $k) {
                    if ($k !== null && $k !== '') {
                        $typesSet[$k] = true;
                    }
                }
            }
            $typesList = array_keys($typesSet);

            // Prévisionnel: heures par enseignement et par type
            $previByEnsType = [];
            $ensIdByLibelle = [];
            // Prévisionnel: heures par enseignant
            $previByEnseignant = [];

            foreach ($data as $previ) {
                $enseignement = $previ->getEnseignement();
                $libelle = $enseignement?->getLibelle();
                $ensId = $enseignement?->getId();
                if ($libelle) {
                    $ensIdByLibelle[$libelle] = $ensId ?? ($ensIdByLibelle[$libelle] ?? null);
                    if (!isset($previByEnsType[$libelle])) {
                        $previByEnsType[$libelle] = [];
                    }
                    $heures = (array) $previ->getHeures();
                    $groupes = (array) $previ->getGroupes();
                    foreach ($typesList as $t) {
                        $h = (float) ($heures[$t] ?? 0.0);
                        $g = array_key_exists($t, $groupes) ? (int) $groupes[$t] : 1;
                        if (!isset($previByEnsType[$libelle][$t])) {
                            $previByEnsType[$libelle][$t] = 0.0;
                        }
                        $previByEnsType[$libelle][$t] += $h * $g;
                    }
                }

                $enseignantDisplay = $previ->getPersonnel()?->getDisplay();
                if ($enseignantDisplay) {
                    if (!isset($previByEnseignant[$enseignantDisplay])) {
                        $previByEnseignant[$enseignantDisplay] = 0.0;
                    }
                    // On additionne le total prévisionnel de cet enregistrement (toutes natures confondues avec groupes)
                    $totalPrevi = 0.0;
                    $heures = (array) $previ->getHeures();
                    $groupes = (array) $previ->getGroupes();
                    foreach ($typesList as $t) {
                        $h = (float) ($heures[$t] ?? 0.0);
                        $g = array_key_exists($t, $groupes) ? (int) $groupes[$t] : 1;
                        $totalPrevi += $h * $g;
                    }
                    $previByEnseignant[$enseignantDisplay] += $totalPrevi;
                }
            }

            // EDT: récupérer les événements correspondants via le repository (filtres: semestre et année universitaire)
            $filters = $context['filters'] ?? [];
            $semestreId = !empty($filters['semestre']) ? (int) $filters['semestre'] : null;
            $anneeUniversitaireId = !empty($filters['anneeUniversitaire']) ? (int) $filters['anneeUniversitaire'] : null;

            $events = $this->edtEventRepository->findForStatsBySemestreAndAnneeUniversitaire($semestreId, $anneeUniversitaireId);

            $edtByEnsType = [];
            $edtByEnseignant = [];

            foreach ($events as $ev) {
                $start = $ev->getDebut();
                $end = $ev->getFin();
                if (!$start || !$end) continue;
                $interval = $start->diff($end);
                $duration = $interval->h + ($interval->days * 24) + ($interval->i / 60.0);

                $libelle = $ev->getEnseignement()?->getLibelle();
                $type = (string) $ev->getType();
                if ($type === '') { $type = 'UNKNOWN'; }
                if ($libelle) {
                    if (!isset($edtByEnsType[$libelle])) {
                        $edtByEnsType[$libelle] = [];
                    }
                    if (!isset($edtByEnsType[$libelle][$type])) {
                        $edtByEnsType[$libelle][$type] = 0.0;
                    }
                    $edtByEnsType[$libelle][$type] += $duration;
                    // Id mapping si pas déjà connu
                    if (!isset($ensIdByLibelle[$libelle])) {
                        $ensIdByLibelle[$libelle] = $ev->getEnseignement()?->getId();
                    }
                }

                $enseignantDisplay = $ev->getPersonnel()?->getDisplay();
                if ($enseignantDisplay) {
                    if (!isset($edtByEnseignant[$enseignantDisplay])) {
                        $edtByEnseignant[$enseignantDisplay] = 0.0;
                    }
                    $edtByEnseignant[$enseignantDisplay] += $duration;
                }
            }

            // Construire les lignes comparatives par enseignement et par type
            $rows = [];
            $allEnsLibs = array_unique(array_merge(array_keys($previByEnsType), array_keys($edtByEnsType)));
            foreach ($allEnsLibs as $libelle) {
                $ensId = $ensIdByLibelle[$libelle] ?? null;
                foreach ($typesList as $t) {
                    $previ = (float) ($previByEnsType[$libelle][$t] ?? 0.0);
                    $edt = (float) ($edtByEnsType[$libelle][$t] ?? 0.0);
                    if ($previ > 0 || $edt > 0) {
                        $rows[] = [
                            'id' => $ensId,
                            'enseignement' => $libelle,
                            'type' => $t,
                            'heures_previsionnel' => $previ,
                            'heures_edt' => $edt,
                            'heures_diff' => $previ - $edt,
                        ];
                    }
                }
            }

            // Construire la comparaison par enseignant
            $allTeachers = array_unique(array_merge(array_keys($previByEnseignant), array_keys($edtByEnseignant)));
            $rowsTeachers = [];
            foreach ($allTeachers as $teacher) {
                $previ = (float) ($previByEnseignant[$teacher] ?? 0.0);
                $edt = (float) ($edtByEnseignant[$teacher] ?? 0.0);
                if ($previ > 0 || $edt > 0) {
                    $rowsTeachers[] = [
                        'enseignant' => $teacher,
                        'heures_previsionnel' => $previ,
                        'heures_edt' => $edt,
                        'heures_diff' => $previ - $edt,
                    ];
                }
            }

            $dto->setStatPreviEdtEnseignement($rows);
            $dto->setStatPreviEdtEnseignant($rowsTeachers);
            $dto->setTypesGroupes($typesList);
            return $dto;
        }

        // Cas item: on effectue un simple pass-through également
        return $this->itemProvider->provide($operation, $uriVariables, $context);
    }
}
