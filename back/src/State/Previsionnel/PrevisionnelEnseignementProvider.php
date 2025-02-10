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
                    $nbHrAttenduCM = $item->getEnseignement()->getHeures()['heures']['CM']['IUT'];
                    $nbHrSaisiCM += $item->getHeures()['heures']['CM'];
                    $nbHrAttenduTD = $item->getEnseignement()->getHeures()['heures']['TD']['IUT'];
                    $nbHrSaisiTD += $item->getHeures()['heures']['TD'];
                    $nbHrAttenduTP = $item->getEnseignement()->getHeures()['heures']['TP']['IUT'];
                    $nbHrSaisiTP += $item->getHeures()['heures']['TP'];

                    $totalCM += $item->getHeures()['heures']['CM'] * $item->getGroupes()['groupes']['CM'];
                    $totalTD += $item->getHeures()['heures']['TD'] * $item->getGroupes()['groupes']['TD'];
                    $totalTP += $item->getHeures()['heures']['TP'] * $item->getGroupes()['groupes']['TP'];

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

        return $prevMatiere;
    }
}
