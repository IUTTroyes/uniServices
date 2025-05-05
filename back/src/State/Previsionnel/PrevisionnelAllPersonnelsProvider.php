<?php

namespace App\State\Previsionnel;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiDto\Previsionnel\PrevisionnelAllPersonnelsDto;
use App\ApiDto\Previsionnel\PrevisionnelEnseignementDto;
use App\Repository\Structure\StructureDepartementPersonnelRepository;

class PrevisionnelAllPersonnelsProvider implements ProviderInterface
{

    public function __construct(
        private CollectionProvider $collectionProvider,
        private ItemProvider $itemProvider,
        private StructureDepartementPersonnelRepository $structureDepartementPersonnelRepository
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof GetCollection) {
            $data = $this->collectionProvider->provide($operation, $uriVariables, $context);

            $totalCM = 0;
            $totalTD = 0;
            $totalTP = 0;

            $totalPermanent = 0;
            $totalVacataire = 0;
            $totalAutre = 0;

            $count = 0;

            $output = [];
            $groupedData = [];
            foreach ($data as $item) {
                if ($item->getPersonnel()) {
                    $personnelId = $item->getPersonnel()->getId();

                    $departement = $this->structureDepartementPersonnelRepository->findOneBy(['personnel' => $item->getPersonnel()->getId(), 'departement' => $context['filters']['departement']]);
                    $departementAffectation = $this->structureDepartementPersonnelRepository->findOneByPersonnelAffectation($item->getPersonnel()->getId());

                    if ($departementAffectation && $departement->getId() === $departementAffectation->getId() || $item->getPersonnel()->getStatut()->getLibelle() === 'Enseignant Vacataire') {
                        $nbHeuresService = $item->getPersonnel()->getNbHeuresService();
                        $affectation = true;
                    } else {
                        $nbHeuresService = 'Service réalisé dans un autre département';
                        $affectation = false;
                    }

                    if (!isset($groupedData[$personnelId])) {
                        $groupedData[$personnelId] = [
                            'count' => $count++,
                            'personnel' => $item->getPersonnel(),
                            'statutLibelle' => $item->getPersonnel()->getStatut()->getLibelle(),
                            'statutBadge' => $item->getPersonnel()->getStatut()->getBadge(),
                            'heures' => [
                                'CM' => 0,
                                'TD' => 0,
                                'TP' => 0,
                            ],
                            'nbHeuresService' => $nbHeuresService,
                            'affectation' => $affectation
                        ];
                    }

                    $groupedData[$personnelId]['heures']['CM'] += $item->getHeures()['CM'];
                    $groupedData[$personnelId]['heures']['TD'] += $item->getHeures()['TD'];
                    $groupedData[$personnelId]['heures']['TP'] += $item->getHeures()['TP'];

                    $totalCM += $item->getHeures()['CM'];
                    $totalTD += $item->getHeures()['TD'];
                    $totalTP += $item->getHeures()['TP'];

                    $totalHeures = $item->getHeures()['CM'] + $item->getHeures()['TD'] + $item->getHeures()['TP'];

                    if (in_array($item->getPersonnel()->getStatut()->value, ['MCF', 'PU', 'ENSAM', 'PRAG', 'PRCE', 'CDD'])) {
                        $totalPermanent += $totalHeures;
                    } elseif (in_array($item->getPersonnel()->getStatut()->value, ['vacataire'])) {
                        $totalVacataire += $totalHeures;
                    } else {
                        $totalAutre += $totalHeures;
                    }
                }
            }

            $output['previ'] = [];
            foreach ($groupedData as $group) {
                $total = $group['heures']['CM'] + $group['heures']['TD'] + $group['heures']['TP'];
                $total = round($total, 2);

                $group['heures']['Total'] = $total;

                if ($group['statutLibelle'] === 'Enseignant Vacataire' && $total < $group['nbHeuresService']) {
                    $diff = 'Peut rester '.$total - $group['nbHeuresService'];

                    // enlever le signe négatif si le nombre est négatif
                    $diff = str_replace('-', '', $diff);
                } elseif ($group['statutLibelle'] === 'Enseignant Vacataire' && $total > $group['nbHeuresService']) {
                    $diff = 'Dépassement de '.$total - $group['nbHeuresService'];
                }
                else {
                    if ($group['affectation']) {
                        $diff = $total - $group['nbHeuresService'];
                    } else {
                        $diff = 'Service réalisé dans un autre département';
                    }
                }
                $group['heures']['Diff'] = $diff;

                $output['previ'][] = $this->toDto($group);
            }

            $totalTotal = $totalCM + $totalTD + $totalTP;
            $output['Total'] = [
                'TotalCM' => round($totalCM, 2),
                'TotalTD' => round($totalTD, 2),
                'TotalTP' => round($totalTP, 2),
                'TotalTotal' => round($totalTotal, 2),
            ];

            $output['repartition'] = [
                // calculer le pourcentage de chaque catégorie par rapport au total
                'Permanent' => round($totalPermanent / $totalTotal * 100, 2),
                'Vacataire' => round($totalVacataire / $totalTotal * 100, 2),
                'Autre' => round($totalAutre / $totalTotal * 100, 2),
            ];

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
        $prevMatiere->setStatut($group['statutBadge']);
        $prevMatiere->setService($group['nbHeuresService']);

        return $prevMatiere;
    }
}
