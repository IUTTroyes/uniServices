<?php

namespace App\State\Previsionnel;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiDto\Previsionnel\PrevisionnelAllPersonnelsDto;
use App\ApiDto\Previsionnel\PrevisionnelEnseignementDto;

class PrevisionnelAllPersonnelsProvider implements ProviderInterface
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

            $heuresCM = 0;
            $heuresTD = 0;
            $heuresTP = 0;

            $total = 0;

            $totalCM = 0;
            $totalTD = 0;
            $totalTP = 0;
            $totalTotal = 0;

            $count = 0;

            $output = [];
            $groupedData = [];
            foreach ($data as $item) {
                if ($item->getPersonnel()) {
                    $personnelId = $item->getPersonnel()->getId();

                    if (!isset($groupedData[$personnelId])) {
                        $heuresCM += $item->getHeures()['heures']['CM'];
                        $heuresTD += $item->getHeures()['heures']['TD'];
                        $heuresTP += $item->getHeures()['heures']['TP'];

                        $groupedData[$personnelId] = [
                            'count' => $count++,
                            'personnel' => $item->getPersonnel(),
                            'statut' => $item->getPersonnel()->getStatut()->getLibelle(),
                            'heures' => [
                                'CM' => $heuresCM,
                                'TD' => $heuresTD,
                                'TP' => $heuresTP,
                            ],
                            'nbHeuresService' => $item->getPersonnel()->getNbHeuresService(),
                        ];
                    }

                    $totalCM += $item->getHeures()['heures']['CM'];
                    $totalTD += $item->getHeures()['heures']['TD'];
                    $totalTP += $item->getHeures()['heures']['TP'];
                }
            }

            $output['previ'] = [];
            foreach ($groupedData as $group) {

                $total = $group['heures']['CM'] + $group['heures']['TD'] + $group['heures']['TP'];
                $group['heures']['Total'] = $total;

                if ($group['statut'] === 'Enseignant Vacataire' && $total < $group['nbHeuresService']) {
                    $diff = 'Peut rester '.$total - $group['nbHeuresService'];
                    // enlever le signe négatif si le nombre est négatif
                    $diff = str_replace('-', '', $diff);
                } elseif ($group['statut'] === 'Enseignant Vacataire' && $total > $group['nbHeuresService']) {
                    $diff = 'Dépassement de '.$total - $group['nbHeuresService'];
                }
                else {
                    $diff = $total - $group['nbHeuresService'];
                }
                $group['heures']['Diff'] = $diff;

                $output['previ'][] = $this->toDto($group);
            }

            $totalTotal = $totalCM + $totalTD + $totalTP;
            $output['Total'] = [
                'TotalCM' => $totalCM,
                'TotalTD' => $totalTD,
                'TotalTP' => $totalTP,
                'TotalTotal' => $totalTotal,
            ];

            // trier par nom de personnel
            usort($output['previ'], function ($a, $b) {
                return $a->getLibelle() <=> $b->getLibelle();
            });

            return $output;
        } else {
            $data = $this->itemProvider->provide($operation, $uriVariables, $context);
        }

        return $this->toDto($data);
    }

    public function toDto($group): PrevisionnelAllPersonnelsDto
    {
        $prevMatiere = new PrevisionnelAllPersonnelsDto();
        $prevMatiere->setLibelle($group['personnel']->getNom().' '.$group['personnel']->getPrenom());
        $prevMatiere->setPersonnel($group['personnel']);
        $prevMatiere->setHeures($group['heures']);
        $prevMatiere->setCount($group['count']);

        return $prevMatiere;
    }
}
