<?php

namespace App\State\Previsionnel;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiDto\Previsionnel\PrevisionnelSemestreDto;

class PrevisionnelSemestreProvider implements ProviderInterface
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
                            ],
                            'groupes' => [
                                'CM' => 0,
                                'TD' => 0,
                                'TP' => 0,
                            ],
                        ];
                    }
                    $groupedData[$enseignementId]['personnels'][] = $item->getPersonnel();
                    $groupedData[$enseignementId]['heures']['CM'] += $item->getHeures()['heures']['CM'];
                    $groupedData[$enseignementId]['heures']['TD'] += $item->getHeures()['heures']['TD'];
                    $groupedData[$enseignementId]['heures']['TP'] += $item->getHeures()['heures']['TP'];
                    $groupedData[$enseignementId]['groupes']['CM'] += $item->getGroupes()['groupes']['CM'];
                    $groupedData[$enseignementId]['groupes']['TD'] += $item->getGroupes()['groupes']['TD'];
                    $groupedData[$enseignementId]['groupes']['TP'] += $item->getGroupes()['groupes']['TP'];

                    $output['previForm'][] = $this->formToDto($item);
                }
            }

            $output['previSynthese'] = [];
            foreach ($groupedData as $group) {

                $totalCM['Maquette'] += $group['enseignement']->getHeures()['heures']['CM']['IUT'];
                $totalCM['Previsionnel'] += $group['heures']['CM'];
                $totalCM['Diff'] += $group['heures']['CM'] - $group['enseignement']->getHeures()['heures']['CM']['IUT'];
                $totalTD['Maquette'] += $group['enseignement']->getHeures()['heures']['TD']['IUT'];
                $totalTD['Previsionnel'] += $group['heures']['TD'];
                $totalTD['Diff'] += $group['heures']['TD'] - $group['enseignement']->getHeures()['heures']['TD']['IUT'];
                $totalTP['Maquette'] += $group['enseignement']->getHeures()['heures']['TP']['IUT'];
                $totalTP['Previsionnel'] += $group['heures']['TP'];
                $totalTP['Diff'] += $group['heures']['TP'] - $group['enseignement']->getHeures()['heures']['TP']['IUT'];

                $output['previSynthese'][] = $this->syntheseToDto($group);
            }

            $output['total'] = [
                'CM' => $totalCM,
                'TD' => $totalTD,
                'TP' => $totalTP,
                'Total' => [
                    'Maquette' => $totalCM['Maquette'] + $totalTD['Maquette'] + $totalTP['Maquette'],
                    'Previsionnel' => $totalCM['Previsionnel'] + $totalTD['Previsionnel'] + $totalTP['Previsionnel'],
                    'Diff' => ($totalCM['Previsionnel'] + $totalTD['Previsionnel'] + $totalTP['Previsionnel']) - ($totalCM['Maquette'] + $totalTD['Maquette'] + $totalTP['Maquette'])
                ]
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
                    'Maquette' => $group['enseignement']->getHeures()['heures']['CM']['IUT'],
                    'Previsionnel' => $group['heures']['CM'],
                    'Diff' => $group['heures']['CM'] - $group['enseignement']->getHeures()['heures']['CM']['IUT']
                ],
                'TD' => [
                    'Maquette' => $group['enseignement']->getHeures()['heures']['TD']['IUT'],
                    'Previsionnel' => $group['heures']['TD'],
                    'Diff' => $group['heures']['TD'] - $group['enseignement']->getHeures()['heures']['TD']['IUT']
                ],
                'TP' => [
                    'Maquette' => $group['enseignement']->getHeures()['heures']['TP']['IUT'],
                    'Previsionnel' => $group['heures']['TP'],
                    'Diff' => $group['heures']['TP'] - $group['enseignement']->getHeures()['heures']['TP']['IUT']
                ],
                'Total' => [
                    'Maquette' => $group['enseignement']->getHeures()['heures']['CM']['IUT'] + $group['enseignement']->getHeures()['heures']['TD']['IUT'] + $group['enseignement']->getHeures()['heures']['TP']['IUT'],
                    'Previsionnel' => $group['heures']['CM'] + $group['heures']['TD'] + $group['heures']['TP'],
                    'Diff' => ($group['heures']['CM'] + $group['heures']['TD'] + $group['heures']['TP']) - ($group['enseignement']->getHeures()['heures']['CM']['IUT'] + $group['enseignement']->getHeures()['heures']['TD']['IUT'] + $group['enseignement']->getHeures()['heures']['TP']['IUT'])
                ]
            ]
        );
        $prevSem->setGroupes($group['groupes']);
        return $prevSem;
    }

    public function formToDto($item): PrevisionnelSemestreDto
    {
        $prevSem = new PrevisionnelSemestreDto();
        $prevSem->setCodeEnseignement($item->getEnseignement()->getCodeEnseignement());
        $prevSem->setLibelleEnseignement($item->getEnseignement()->getDisplay());
        $prevSem->setTypeEnseignement($item->getEnseignement()->getType());
        $prevSem->setPersonnels([$item->getPersonnel()]);
        $prevSem->setIntervenant($item->getPersonnel()->getDisplay());
        $prevSem->setHeures(
            [
                'CM' => [
                    'NbHrGrp' => $item->getHeures()['heures']['CM'],
                    'NbGrp' => $item->getGroupes()['groupes']['CM'],
                    'NbSeanceGrp' => $item->getHeures()['heures']['CM'] / $item::DUREE_SEANCE,
                ],
                'TD' => [
                    'NbHrGrp' => $item->getHeures()['heures']['TD'],
                    'NbGrp' => $item->getGroupes()['groupes']['TD'],
                    'NbSeanceGrp' => $item->getHeures()['heures']['TD'] / $item::DUREE_SEANCE,
                ],
                'TP' => [
                    'NbHrGrp' => $item->getHeures()['heures']['TP'],
                    'NbGrp' => $item->getGroupes()['groupes']['TP'],
                    'NbSeanceGrp' => $item->getHeures()['heures']['TP'] / $item::DUREE_SEANCE,
                ],
            ]
        );
        $prevSem->setGroupes($item->getGroupes());
        return $prevSem;
    }
}
