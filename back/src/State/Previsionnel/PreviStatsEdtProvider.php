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

            // Prévisionnel: heures par enseignement (clé = id) et par type
            $previByEnsType = [];
            // Mapping affichage par id d'enseignement
            $ensDisplayById = [];
            // Prévisionnel: heures par enseignant et par type (utilise la même logique que PrevisionnelPersonnelProvider)
            $previByEnseignantType = [];

            foreach ($data as $previ) {
                $enseignement = $previ->getEnseignement();
                $ensId = $enseignement?->getId();
                if ($ensId) {
                    $libelleDisplay = ($enseignement->getCodeEnseignement() ?? '').'-'.$enseignement?->getLibelle();
                    $ensDisplayById[$ensId] = $libelleDisplay;
                    if (!isset($previByEnsType[$ensId])) {
                        $previByEnsType[$ensId] = [];
                    }
                    $heures = (array) $previ->getHeures();
                    $groupes = (array) $previ->getGroupes();
                    foreach ($typesList as $t) {
                        $h = (float) ($heures[$t] ?? 0.0);
                        $g = array_key_exists($t, $groupes) ? (int) $groupes[$t] : 1;
                        if (!isset($previByEnsType[$ensId][$t])) {
                            $previByEnsType[$ensId][$t] = 0.0;
                        }
                        $previByEnsType[$ensId][$t] += $h * $g;
                    }
                }

                $enseignantDisplay = $previ->getPersonnel()?->getDisplay();
                if ($enseignantDisplay) {
                    if (!isset($previByEnseignantType[$enseignantDisplay])) {
                        $previByEnseignantType[$enseignantDisplay] = [];
                    }
                    $heures = (array) $previ->getHeures();
                    $groupes = (array) $previ->getGroupes();
                    foreach ($typesList as $t) {
                        $h = (float) ($heures[$t] ?? 0.0);
                        $g = array_key_exists($t, $groupes) ? (int) $groupes[$t] : 1;
                        if (!isset($previByEnseignantType[$enseignantDisplay][$t])) {
                            $previByEnseignantType[$enseignantDisplay][$t] = 0.0;
                        }
                        $previByEnseignantType[$enseignantDisplay][$t] += $h * $g;
                    }
                }
            }

            // EDT: récupérer les événements correspondants via le repository (filtres: semestre et année universitaire)
            $filters = $context['filters'] ?? [];
            $semestreId = !empty($filters['semestre']) ? (int) $filters['semestre'] : null;
            $anneeUniversitaireId = !empty($filters['anneeUniversitaire']) ? (int) $filters['anneeUniversitaire'] : null;

            $events = $this->edtEventRepository->findForStatsBySemestreAndAnneeUniversitaire($semestreId, $anneeUniversitaireId);

            $edtByEnsType = [];
            // EDT: heures par enseignant et par type
            $edtByEnseignantType = [];

            foreach ($events as $ev) {
                $start = $ev->getDebut();
                $end = $ev->getFin();
                if (!$start || !$end) continue;
                $interval = $start->diff($end);
                $duration = $interval->h + ($interval->days * 24) + ($interval->i / 60.0);

                $ens = $ev->getEnseignement();
                $ensId = $ens?->getId();
                $type = (string) $ev->getType();
                if ($type === '') { $type = 'UNKNOWN'; }
                if ($ensId) {
                    if (!isset($edtByEnsType[$ensId])) {
                        $edtByEnsType[$ensId] = [];
                    }
                    if (!isset($edtByEnsType[$ensId][$type])) {
                        $edtByEnsType[$ensId][$type] = 0.0;
                    }
                    $edtByEnsType[$ensId][$type] += $duration;
                    // Mapping affichage par id si pas déjà connu
                    if (!isset($ensDisplayById[$ensId])) {
                        $code = $ens->getCodeEnseignement() ?? '';
                        $lib = $ens->getLibelle();
                        $ensDisplayById[$ensId] = $code.'-'.$lib;
                    }
                }

                $enseignantDisplay = $ev->getPersonnel()?->getDisplay();
                if ($enseignantDisplay) {
                    if (!isset($edtByEnseignantType[$enseignantDisplay])) {
                        $edtByEnseignantType[$enseignantDisplay] = [];
                    }
                    if (!isset($edtByEnseignantType[$enseignantDisplay][$type])) {
                        $edtByEnseignantType[$enseignantDisplay][$type] = 0.0;
                    }
                    $edtByEnseignantType[$enseignantDisplay][$type] += $duration;
                }
            }

            // Construire les lignes comparatives par enseignement (clé = id) et par type
            $rows = [];
            $allEnsIds = array_unique(array_merge(array_keys($previByEnsType), array_keys($edtByEnsType)));
            foreach ($allEnsIds as $ensId) {
                $display = $ensDisplayById[$ensId] ?? '';
                foreach ($typesList as $t) {
                    $previ = (float) ($previByEnsType[$ensId][$t] ?? 0.0);
                    $edt = (float) ($edtByEnsType[$ensId][$t] ?? 0.0);
                    if ($previ > 0 || $edt > 0) {
                        $heures_diff = $previ - $edt;

                        $rows[] = [
                            'id' => $ensId,
                            'enseignement' => $display,
                            'type' => $t,
                            'heures_previsionnel' => $previ,
                            'heures_edt' => $edt,
                            'heures_diff' => $heures_diff,
                        ];
                    }
                }
            }

            // Construire la comparaison par enseignant et par type
            $allTeachers = array_unique(array_merge(array_keys($previByEnseignantType), array_keys($edtByEnseignantType)));
            // Construire $rowsTeachers en profitant de l'ordre des événements (tri DB par personnel.nom/prenom)
            $rowsTeachers = [];
            $seenTeachers = [];
            // On parcourt d'abord les enseignants rencontrés dans les événements pour préserver l'ordre
            foreach ($events as $ev) {
                $enseignantDisplay = $ev->getPersonnel()?->getDisplay();
                if (!$enseignantDisplay) continue;
                // éviter doublons
                if (isset($seenTeachers[$enseignantDisplay])) continue;
                $seenTeachers[$enseignantDisplay] = true;
                foreach ($typesList as $t) {
                    $previ = (float) ($previByEnseignantType[$enseignantDisplay][$t] ?? 0.0);
                    $edt = (float) ($edtByEnseignantType[$enseignantDisplay][$t] ?? 0.0);
                    if ($previ > 0 || $edt > 0) {
                        $rowsTeachers[] = [
                            'enseignant' => $enseignantDisplay,
                            'type' => $t,
                            'heures_previsionnel' => $previ,
                            'heures_edt' => $edt,
                            'heures_diff' => $previ - $edt,
                        ];
                    }
                }
            }

            // Puis compléter avec les enseignants provenant du prévisionnel qui n'apparaissent pas dans les événements
            foreach ($allTeachers as $teacher) {
                if (isset($seenTeachers[$teacher])) continue;
                foreach ($typesList as $t) {
                    $previ = (float) ($previByEnseignantType[$teacher][$t] ?? 0.0);
                    $edt = (float) ($edtByEnseignantType[$teacher][$t] ?? 0.0);
                    if ($previ > 0 || $edt > 0) {
                        $rowsTeachers[] = [
                            'enseignant' => $teacher,
                            'type' => $t,
                            'heures_previsionnel' => $previ,
                            'heures_edt' => $edt,
                            'heures_diff' => $previ - $edt,
                        ];
                    }
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
