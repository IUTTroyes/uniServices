<?php

namespace IntranetBundle\State\Provider\Previsionnel;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Scolarite\ScolEnseignement;
use IntranetBundle\Dto\Previsionnel\PrevisionnelAllPersonnelsDto;
use IntranetBundle\Dto\Previsionnel\PrevisionnelEnseignementDto;
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

            $departementId = $operation->getParameters()?->get('departement')?->getValue(null)
                ?? $context['filters']['departement'] ?? null;
            $personnelsAffectes = [];
            if ($personnelIds !== [] && $departementId !== null) {
                $affectations = $this->structureDepartementPersonnelRepository->findBy([
                    'personnel' => array_unique($personnelIds),
                    'departement' => $departementId,
                    'affectation' => true,
                ]);
                foreach ($affectations as $affectation) {
                    $personnelsAffectes[$affectation->getPersonnel()->getId()] = true;
                }
            }

            foreach ($data as $item) {
                if ($item->getPersonnel()) {
                    $personnel = $item->getPersonnel();
                    $personnelId = $personnel->getId();
                    $statut = $personnel->getStatut();
                    $statutLibelle = $statut?->getLibelle() ?? '';

                    if (isset($personnelsAffectes[$personnelId])
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
                            'statutBadge' => $statut?->getBadge() ?? '',
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

                    $cmHours = (float) ($heures['CM'] ?? 0);
                    $tdHours = (float) ($heures['TD'] ?? 0);
                    $tpHours = (float) ($heures['TP'] ?? 0);

                    $groupedData[$personnelId]['heures']['CM'] += $cmHours;
                    $groupedData[$personnelId]['heures']['TD'] += $tdHours;
                    $groupedData[$personnelId]['heures']['TP'] += $tpHours;

                    $totalCM += $cmHours;
                    $totalTD += $tdHours;
                    $totalTP += $tpHours;

                    $totalHeures = $cmHours + $tdHours + $tpHours;

                    $statutValue = $statut?->value;
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
                $equivalentTd = round($group['heures']['CM'] * ScolEnseignement::MAJORATION_CM + $group['heures']['TD'] + $group['heures']['TP'], 2);
                $group['heures']['EquivalentTd'] = $equivalentTd;

                if ($group['statutLibelle'] === 'Enseignant Vacataire' && $equivalentTd < $group['nbHeuresService']) {
                    $diffValue = $equivalentTd - $group['nbHeuresService'];
                    $diff = 'Peut rester ' . abs($diffValue);
                } elseif ($group['statutLibelle'] === 'Enseignant Vacataire' && $equivalentTd > $group['nbHeuresService']) {
                    $diffValue = $equivalentTd - $group['nbHeuresService'];
                    $diff = 'Dépassement de ' . $diffValue;
                }
                else {
                    if ($group['affectation']) {
                        $diff = $equivalentTd - $group['nbHeuresService'];
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

        $prevMatiere->setLibelle($personnel->getNom() . ' ' .$personnel->getPrenom());
        $prevMatiere->setPersonnel($personnel);
        $prevMatiere->setHeures($group['heures']);
        $prevMatiere->setCount($group['count']);
        $prevMatiere->setStatut($group['statutBadge']);
        $prevMatiere->setService($group['nbHeuresService']);

        return $prevMatiere;
    }
}
