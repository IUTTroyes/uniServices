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

            // Pre-fetch department data to avoid queries in the loop
            $personnelIds = [];
            foreach ($data as $item) {
                if ($item->getPersonnel()) {
                    $personnelIds[] = $item->getPersonnel()->getId();
                }
            }

            // Batch fetch department data
            $departementMap = [];
            $departementAffectationMap = [];
            if (!empty($personnelIds)) {
                $departements = $this->structureDepartementPersonnelRepository->findBy([
                    'personnel' => $personnelIds,
                    'departement' => $context['filters']['departement']
                ]);

                foreach ($departements as $dept) {
                    $departementMap[$dept->getPersonnel()->getId()] = $dept;
                }

                // Fetch all affectations in one query
                $affectations = [];
                foreach ($personnelIds as $id) {
                    $affectation = $this->structureDepartementPersonnelRepository->findOneByPersonnelAffectation($id);
                    if ($affectation) {
                        $departementAffectationMap[$id] = $affectation;
                    }
                }
            }

            foreach ($data as $item) {
                if ($item->getPersonnel()) {
                    $personnel = $item->getPersonnel();
                    $personnelId = $personnel->getId();
                    $statut = $personnel->getStatut();
                    $statutLibelle = $statut->getLibelle();

                    $departement = $departementMap[$personnelId] ?? null;
                    $departementAffectation = $departementAffectationMap[$personnelId] ?? null;

                    if ($departementAffectation && $departement && $departement->getId() === $departementAffectation->getId()
                        || $statutLibelle === 'Enseignant Vacataire') {
                        $nbHeuresService = $personnel->getNbHeuresService();
                        $affectation = true;
                    } else {
                        $nbHeuresService = 'Service réalisé dans un autre département';
                        $affectation = false;
                    }

                    if (!isset($groupedData[$personnelId])) {
                        $groupedData[$personnelId] = [
                            'count' => $count++,
                            'personnel' => $personnel,
                            'statutLibelle' => $statutLibelle,
                            'statutBadge' => $statut->getBadge(),
                            'heures' => [
                                'CM' => 0,
                                'TD' => 0,
                                'TP' => 0,
                            ],
                            'nbHeuresService' => $nbHeuresService,
                            'affectation' => $affectation
                        ];
                    }

                    // Cache these values to avoid repeated array access
                    $heures = $item->getHeures();
                    $groupes = $item->getGroupes();

                    $cmHours = $heures['CM'] * $groupes['CM'];
                    $tdHours = $heures['TD'] * $groupes['TD'];
                    $tpHours = $heures['TP'] * $groupes['TP'];

                    $groupedData[$personnelId]['heures']['CM'] += $cmHours;
                    $groupedData[$personnelId]['heures']['TD'] += $tdHours;
                    $groupedData[$personnelId]['heures']['TP'] += $tpHours;

                    $totalCM += $cmHours;
                    $totalTD += $tdHours;
                    $totalTP += $tpHours;

                    $totalHeures = $cmHours + $tdHours + $tpHours;

                    $statutValue = $statut->value;
                    if (in_array($statutValue, ['MCF', 'PU', 'ENSAM', 'PRAG', 'PRCE', 'CDD'])) {
                        $totalPermanent += $totalHeures;
                    } elseif (in_array($statutValue, ['vacataire'])) {
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
                    $diffValue = $total - $group['nbHeuresService'];
                    $diff = 'Peut rester ' . abs($diffValue);
                } elseif ($group['statutLibelle'] === 'Enseignant Vacataire' && $total > $group['nbHeuresService']) {
                    $diffValue = $total - $group['nbHeuresService'];
                    $diff = 'Dépassement de ' . $diffValue;
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
                'Permanent' => $totalTotal > 0 ? round($totalPermanent / $totalTotal * 100, 2) : 0,
                'Vacataire' => $totalTotal > 0 ? round($totalVacataire / $totalTotal * 100, 2) : 0,
                'Autre' => $totalTotal > 0 ? round($totalAutre / $totalTotal * 100, 2) : 0,
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

        // Cache the personnel object to avoid repeated access
        $personnel = $group['personnel'];

        $prevMatiere->setLibelle($personnel->getNom().' '.$personnel->getPrenom());
        $prevMatiere->setPersonnel($personnel);
        $prevMatiere->setHeures($group['heures']);
        $prevMatiere->setCount($group['count']);
        $prevMatiere->setStatut($group['statutBadge']);
        $prevMatiere->setService($group['nbHeuresService']);

        return $prevMatiere;
    }
}
