<?php

namespace App\State\Previsionnel;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiDto\Previsionnel\PrevisionnelEnseignementDto;

class PrevisionnelEnseignementProvider implements ProviderInterface
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

            if (empty($data)) {
                $output['previ'] = [];

                return $output;
            }

            $output = [];
            $nbHrAttenduCM = 0;
            $nbHrSaisiCM = 0;
            $nbHrAttenduTD = 0;
            $nbHrSaisiTD = 0;
            $nbHrAttenduTP = 0;
            $nbHrSaisiTP = 0;
            $totalCM = 0;
            $totalTD = 0;
            $totalTP = 0;
            foreach ($data as $item) {
                if ($item->getPersonnel()) {
                    $nbHrAttenduCM = $item->getEnseignement()->getHeures()['CM']['IUT'];
                    $nbHrSaisiCM += $item->getHeures()['CM'];
                    $nbHrAttenduTD = $item->getEnseignement()->getHeures()['TD']['IUT'];
                    $nbHrSaisiTD += $item->getHeures()['TD'];
                    $nbHrAttenduTP = $item->getEnseignement()->getHeures()['TP']['IUT'];
                    $nbHrSaisiTP += $item->getHeures()['TP'];

                    $totalCM += $item->getHeures()['CM'] * $item->getGroupes()['CM'];
                    $totalTD += $item->getHeures()['TD'] * $item->getGroupes()['TD'];
                    $totalTP += $item->getHeures()['TP'] * $item->getGroupes()['TP'];

                    $output['previ'][] = $this->toDto($item);
                }
            }

            $output['verifTotalEtudiant'] = [
                'CM' => [
                    'NbHrAttendu' => $nbHrAttenduCM,
                    'NbHrSaisi' => $nbHrSaisiCM,
                    'Diff' => $nbHrSaisiCM - $nbHrAttenduCM,
                ],
                'TD' => [
                    'NbHrAttendu' => $nbHrAttenduTD,
                    'NbHrSaisi' => $nbHrSaisiTD,
                    'Diff' => $nbHrSaisiTD - $nbHrAttenduTD,
                ],
                'TP' => [
                    'NbHrAttendu' => $nbHrAttenduTP,
                    'NbHrSaisi' => $nbHrSaisiTP,
                    'Diff' => $nbHrSaisiTP - $nbHrAttenduTP,
                ],
            ];

            $output['Total'] = [
                'TotalCM' => $totalCM,
                'TotalTD' => $totalTD,
                'TotalTP' => $totalTP,
            ];

            $output['TotalEquTd'] = [
                'TotalClassique' => $totalCM + $totalTD + $totalTP,
                'TotalTd' => $totalCM * $item->getEnseignement()::MAJORATION_CM + $totalTD + $totalTP,
            ];

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
                'CM' => [
                    'NbHrGrp' => $item->getHeures()['CM'],
                    'NbGrp' => $item->getGroupes()['CM'],
                    'NbSeanceGrp' => $item->getHeures()['CM'] / $item::DUREE_SEANCE,
                ],
                'TD' => [
                    'NbHrGrp' => $item->getHeures()['TD'],
                    'NbGrp' => $item->getGroupes()['TD'],
                    'NbSeanceGrp' => $item->getHeures()['TD'] / $item::DUREE_SEANCE,
                ],
                'TP' => [
                    'NbHrGrp' => $item->getHeures()['TP'],
                    'NbGrp' => $item->getGroupes()['TP'],
                    'NbSeanceGrp' => $item->getHeures()['TP'] / $item::DUREE_SEANCE,
                ],
            ]
        );

        return $prevMatiere;
    }
}
