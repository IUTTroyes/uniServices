<?php

namespace App\State\Previsionnel;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiDto\Previsionnel\PrevisionnelSemestreDto;
use App\Repository\Structure\StructureSemestreRepository;

class PrevisionnelSemestreProvider implements ProviderInterface
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

            $totalCM = [
                'Maquette' => 0,
                'Previsionnel' => 0,
                'Diff' => 0
            ];
            $totalTD = [
                'Maquette' => 0,
                'Previsionnel' => 0,
                'Diff' => 0
            ];
            $totalTP = [
                'Maquette' => 0,
                'Previsionnel' => 0,
                'Diff' => 0
            ];

            $output['previForm'] = [];

            $nbHrAttenduCM = 0;
            $nbHrSaisiCM = 0;
            $nbHrAttenduTD = 0;
            $nbHrSaisiTD = 0;
            $nbHrAttenduTP = 0;
            $nbHrSaisiTP = 0;
            $groupedData = [];
            foreach ($data as $item) {
                if ($item->getPersonnel()) {
                    $enseignementId = $item->getEnseignement()->getId();

                    if (!isset($groupedData[$enseignementId])) {
                        $groupedData[$enseignementId] = [
                            'enseignement' => $item->getEnseignement(),
                            'personnels' => [],
                            'heures' => [
                                'CM' => 0,
                                'TD' => 0,
                                'TP' => 0,
                                'Projet' => 0,
                            ],
                            'groupes' => [
                                'CM' => 0,
                                'TD' => 0,
                                'TP' => 0,
                                'Projet' => 0,
                            ],
                        ];
                    }
                    $groupedData[$enseignementId]['personnels'][] = $item->getPersonnel();
                    $groupedData[$enseignementId]['heures']['CM'] += $item->getHeures()['CM'];
                    $groupedData[$enseignementId]['heures']['TD'] += $item->getHeures()['TD'];
                    $groupedData[$enseignementId]['heures']['TP'] += $item->getHeures()['TP'];
                    $groupedData[$enseignementId]['groupes']['CM'] += $item->getGroupes()['CM'];
                    $groupedData[$enseignementId]['groupes']['TD'] += $item->getGroupes()['TD'];
                    $groupedData[$enseignementId]['groupes']['TP'] += $item->getGroupes()['TP'];

                    $output['previForm'][] = $this->formToDto($item);

                    $semestre = $this->semestreRepository->find($context['filters']['semestre']);

                    $nbHrSaisiCM += $item->getGroupes()['CM'] !== 0
                        ? ($item->getGroupes()['CM'] % $semestre->getNbGroupesCm() === 0
                            ? $item->getHeures()['CM'] / $semestre->getNbGroupesCm()
                            : $item->getHeures()['CM'] / $item->getGroupes()['CM'])
                        : 0;

                    $nbHrSaisiTD += $item->getGroupes()['TD'] !== 0
                        ? ($item->getGroupes()['TD'] % $semestre->getNbGroupesTd() === 0
                            ? $item->getHeures()['TD'] / $semestre->getNbGroupesTd()
                            : $item->getHeures()['TD'] / $item->getGroupes()['TD'])
                        : 0;

                    $nbHrSaisiTP += $item->getGroupes()['TP'] !== 0
                        ? ($item->getGroupes()['TP'] % $semestre->getNbGroupesTp() === 0
                            ? $item->getHeures()['TP'] / $semestre->getNbGroupesTp()
                            : $item->getHeures()['TP'] / $item->getGroupes()['TP'])
                        : 0;
                }
            }

            $output['previSynthese'] = [];
            foreach ($groupedData as $group) {
                // todo: à vérifier -> est-ce qu'on prend les heures une seule fois par enseignement ?
                $nbHrAttenduCM += $item->getEnseignement()->getHeures()['CM']['IUT'];
                $nbHrAttenduTD += $item->getEnseignement()->getHeures()['TD']['IUT'];
                $nbHrAttenduTP += $item->getEnseignement()->getHeures()['TP']['IUT'];

                $totalCM['Maquette'] += $group['enseignement']->getHeures()['CM']['IUT'];
                $totalCM['Previsionnel'] += $group['heures']['CM'];
                $totalCM['Diff'] += $group['heures']['CM'] - $group['enseignement']->getHeures()['CM']['IUT'];
                $totalTD['Maquette'] += $group['enseignement']->getHeures()['TD']['IUT'];
                $totalTD['Previsionnel'] += $group['heures']['TD'];
                $totalTD['Diff'] += $group['heures']['TD'] - $group['enseignement']->getHeures()['TD']['IUT'];
                $totalTP['Maquette'] += $group['enseignement']->getHeures()['TP']['IUT'];
                $totalTP['Previsionnel'] += $group['heures']['TP'];
                $totalTP['Diff'] += $group['heures']['TP'] - $group['enseignement']->getHeures()['TP']['IUT'];

                $output['previSynthese'][] = $this->syntheseToDto($group);
            }

            $totalCM['Maquette'] = round($totalCM['Maquette'], 1);
            $totalCM['Previsionnel'] = round($totalCM['Previsionnel'], 1);
            $totalCM['Diff'] = round($totalCM['Previsionnel'] - $totalCM['Maquette'], 1);
            $totalTD['Maquette'] = round($totalTD['Maquette'], 1);
            $totalTD['Previsionnel'] = round($totalTD['Previsionnel'], 1);
            $totalTD['Diff'] = round($totalTD['Previsionnel'] - $totalTD['Maquette'], 1);
            $totalTP['Maquette'] = round($totalTP['Maquette'], 1);
            $totalTP['Previsionnel'] = round($totalTP['Previsionnel'], 1);
            $totalTP['Diff'] = round($totalTP['Previsionnel'] - $totalTP['Maquette'], 1);

            $output['total'] = [
                'CM' => $totalCM,
                'TD' => $totalTD,
                'TP' => $totalTP,
                'Total' => [
                    'Maquette' => round($totalCM['Maquette'] + $totalTD['Maquette'] + $totalTP['Maquette'], 1),
                    'Previsionnel' => round($totalCM['Previsionnel'] + $totalTD['Previsionnel'] + $totalTP['Previsionnel'], 1),
                    'Diff' => round(($totalCM['Previsionnel'] + $totalTD['Previsionnel'] + $totalTP['Previsionnel']) - ($totalCM['Maquette'] + $totalTD['Maquette'] + $totalTP['Maquette']), 1)
                ]
            ];

            $output['verifTotalEtudiant'] = [
                'CM' => [
                    'NbHrAttendu' => $nbHrAttenduCM,
                    'NbHrSaisi' => round($nbHrSaisiCM, 1),
                    'Diff' => round($nbHrSaisiCM - $nbHrAttenduCM, 1),
                ],
                'TD' => [
                    'NbHrAttendu' => $nbHrAttenduTD,
                    'NbHrSaisi' => round($nbHrSaisiTD, 1),
                    'Diff' => round($nbHrSaisiTD - $nbHrAttenduTD, 1),
                ],
                'TP' => [
                    'NbHrAttendu' => $nbHrAttenduTP,
                    'NbHrSaisi' => round($nbHrSaisiTP, 1),
                    'Diff' => round($nbHrSaisiTP - $nbHrAttenduTP, 1),
                ],
            ];

            $output['totalForm'] = [
                'CM' => $totalCM['Previsionnel'],
                'TD' => $totalTD['Previsionnel'],
                'TP' => $totalTP['Previsionnel'],
                'Total' => $totalCM['Previsionnel'] + $totalTD['Previsionnel'] + $totalTP['Previsionnel'],
            ];

            $output['TotalEquTd'] = [
                'TotalClassique' => round($totalCM['Previsionnel'] + $totalTD['Previsionnel'] + $totalTP['Previsionnel'], 1),
                'TotalTd' => round($totalCM['Previsionnel'] * $item->getEnseignement()::MAJORATION_CM + $totalTD['Previsionnel'] + $totalTP['Previsionnel'], 1),
            ];

            return $output;
        } else {
            $data = $this->itemProvider->provide($operation, $uriVariables, $context);
        }

        return $this->syntheseToDto($data);
    }

    public function syntheseToDto($group): PrevisionnelSemestreDto
    {
        $prevSem = new PrevisionnelSemestreDto();
        $prevSem->setCodeEnseignement($group['enseignement']->getCodeEnseignement());
        $prevSem->setLibelleEnseignement($group['enseignement']->getLibelle());
        $prevSem->setTypeEnseignement($group['enseignement']->getType());
        $prevSem->setPersonnels($group['personnels']);
        $prevSem->setHeures(
            [
                'CM' => [
                    'Maquette' => round($group['enseignement']->getHeures()['CM']['IUT'], 1),
                    'Previsionnel' => round($group['heures']['CM'], 1),
                    'Diff' => round($group['heures']['CM'] - $group['enseignement']->getHeures()['CM']['IUT'], 1)
                ],
                'TD' => [
                    'Maquette' => round($group['enseignement']->getHeures()['TD']['IUT'], 1),
                    'Previsionnel' => round($group['heures']['TD'], 1),
                    'Diff' => round($group['heures']['TD'] - $group['enseignement']->getHeures()['TD']['IUT'], 1)
                ],
                'TP' => [
                    'Maquette' => round($group['enseignement']->getHeures()['TP']['IUT'], 1),
                    'Previsionnel' => round($group['heures']['TP'], 1),
                    'Diff' => round($group['heures']['TP'] - $group['enseignement']->getHeures()['TP']['IUT'], 1)
                ],
                'Total' => [
                    'Maquette' => round($group['enseignement']->getHeures()['CM']['IUT'] + $group['enseignement']->getHeures()['TD']['IUT'] + $group['enseignement']->getHeures()['TP']['IUT'], 1),
                    'Previsionnel' => round($group['heures']['CM'] + $group['heures']['TD'] + $group['heures']['TP'], 1),
                    'Diff' => round(($group['heures']['CM'] + $group['heures']['TD'] + $group['heures']['TP']) - ($group['enseignement']->getHeures()['CM']['IUT'] + $group['enseignement']->getHeures()['TD']['IUT'] + $group['enseignement']->getHeures()['TP']['IUT']), 1)
                ]
            ]
        );
        $prevSem->setGroupes($group['groupes']);
        return $prevSem;
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
                    'NbSeanceGrp' => round((($item->getGroupes()['CM'] !== 0 ? $item->getHeures()['CM']/$item->getGroupes()['CM'] : $item->getHeures()['CM']) / $item::DUREE_SEANCE) * $item->getGroupes()['CM'], 1),
                ],
                'TD' => [
                    'NbHrGrp' => round($item->getGroupes()['TD'] !== 0 ? $item->getHeures()['TD']/$item->getGroupes()['TD'] : $item->getHeures()['TD'], 1),
                    'NbGrp' => $item->getGroupes()['TD'],
                    'NbSeanceGrp' => round((($item->getGroupes()['TD'] !== 0 ? $item->getHeures()['TD']/$item->getGroupes()['TD'] : $item->getHeures()['TD']) / $item::DUREE_SEANCE) * $item->getGroupes()['TD'], 1),
                ],
                'TP' => [
                    'NbHrGrp' => round($item->getGroupes()['TP'] !== 0 ? $item->getHeures()['TP']/$item->getGroupes()['TP'] : $item->getHeures()['TP'], 1),
                    'NbGrp' => $item->getGroupes()['TP'],
                    'NbSeanceGrp' => round((($item->getGroupes()['TP'] !== 0 ? $item->getHeures()['TP']/$item->getGroupes()['TP'] : $item->getHeures()['TP']) / $item::DUREE_SEANCE) * $item->getGroupes()['TP'], 1),
                ],
                'Projet' => [
                    'NbHrGrp' => $item->getHeures()['Projet'],
                    'NbGrp' => $item->getGroupes()['Projet'],
                    'NbSeanceGrp' => ($item->getHeures()['Projet'] / $item::DUREE_SEANCE),
                ],
            ]
        );
        $prevSem->setGroupes($item->getGroupes());
        $prevSem->setAnneeUniversitaire($item->getAnneeUniversitaire());

        return $prevSem;
    }
}
