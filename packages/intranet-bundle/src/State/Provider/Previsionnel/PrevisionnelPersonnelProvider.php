<?php

namespace IntranetBundle\State\Provider\Previsionnel;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use IntranetBundle\Dto\Previsionnel\PrevisionnelPersonnelDto;
use App\Repository\Structure\StructureDepartementPersonnelRepository;

class PrevisionnelPersonnelProvider implements ProviderInterface
{

    public function __construct(
        private CollectionProvider $collectionProvider,
        private ItemProvider $itemProvider,
        private StructureDepartementPersonnelRepository $structureDepartementPersonnelRepository,
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof GetCollection) {
            $data = $this->collectionProvider->provide($operation, $uriVariables, $context);

            $output = [
                'previForm' => [],
                'totalForm' => [
                    'CM' => 0,
                    'TD' => 0,
                    'TP' => 0,
                    'Total' => 0,
                ],
                'synthese' => [
                    'Service' => 0,
                    'Diff' => 0,
                    'TotalClassique' => 0,
                    'TotalTd' => 0,
                ],
                'personnel' => [
                    'class' => '',
                    'statutSeverity' => 'secondary',
                    'icon' => '',
                    'statut' => '',
                ],
            ];

            if (empty($data)) {
                return array_values($output);
            }

            $totalCm = 0.0;
            $totalTd = 0.0;
            $totalTp = 0.0;
            $personnel = null;

            foreach ($data as $item) {
                if (null === $item->getPersonnel() || null === $item->getEnseignement()) {
                    continue;
                }

                $personnel = $item->getPersonnel();
                $output['previForm'][] = $this->toDto($item);

                $totalCm += (float) $item->getHeures()['CM'];
                $totalTd += (float) $item->getHeures()['TD'];
                $totalTp += (float) $item->getHeures()['TP'];
            }

            $totalClassique = round($totalCm + $totalTd + $totalTp, 1);
            $totalTdEquiv = round($totalCm * 1.5 + $totalTd + $totalTp, 1);

            $nbHeuresService = 0;
            $diff = 0;
            $statut = [
                'class' => '',
                'statutSeverity' => 'secondary',
                'icon' => '',
                'statut' => '',
            ];

            if (null !== $personnel) {
                $personnelId = $personnel->getId();
                $departementId = $context['filters']['departement'] ?? null;

                $departement = null;
                if (null !== $departementId) {
                    $departement = $this->structureDepartementPersonnelRepository->findOneBy([
                        'personnel' => $personnelId,
                        'departement' => $departementId,
                    ]);
                }

                $departementAffectation = $this->structureDepartementPersonnelRepository->findOneByPersonnelAffectation($personnelId);
                $isVacataire = $personnel->getStatut() && $personnel->getStatut()->getLibelle() === 'Enseignant Vacataire';

                $hasServiceInDepartement = ($departementAffectation && $departement && $departement->getId() === $departementAffectation->getId()) || $isVacataire;

                if ($hasServiceInDepartement) {
                    $nbHeuresService = (float) $personnel->getNbHeuresService();
                    $diff = round($nbHeuresService - $totalTdEquiv, 1);

                    if ($diff > 0) {
                        $statut = [
                            'class' => '!bg-green-400 !text-white',
                            'statutSeverity' => 'success',
                            'icon' => 'pi pi-check',
                            'statut' => 'Peut rester ' . $diff . ' h',
                        ];
                    } elseif ($diff < 0) {
                        $statut = [
                            'class' => '!bg-amber-400 !text-white',
                            'statutSeverity' => 'warn',
                            'icon' => 'pi pi-exclamation-triangle',
                            'statut' => 'Dépassement de ' . abs($diff) . ' h',
                        ];
                    } else {
                        $statut = [
                            'class' => '!bg-blue-400 !text-white',
                            'statutSeverity' => 'success',
                            'icon' => 'pi pi-check',
                            'statut' => 'Service équilibré',
                        ];
                    }
                } else {
                    $nbHeuresService = 'Service réalisé dans un autre département';
                    $diff = 'Service réalisé dans un autre département';
                    $statut = [
                        'class' => '!bg-gray-100 !text-gray-800',
                        'statutSeverity' => 'secondary',
                        'icon' => '',
                        'statut' => 'Service dans un autre département',
                    ];
                }
            }

            $output['totalForm'] = [
                'CM' => round($totalCm, 1),
                'TD' => round($totalTd, 1),
                'TP' => round($totalTp, 1),
                'Total' => $totalClassique,
            ];
            $output['synthese'] = [
                'Service' => $nbHeuresService,
                'Diff' => $diff,
                'TotalClassique' => $totalClassique,
                'TotalTd' => $totalTdEquiv,
            ];
            $output['personnel'] = $statut;

            return array_values($output);
        } else {
            $data = $this->itemProvider->provide($operation, $uriVariables, $context);
        }

        return $this->toDto($data);
    }

    public function toDto($item): PrevisionnelPersonnelDto
    {
        $prevPers = new PrevisionnelPersonnelDto();
        $prevPers->setId($item->getId());
        $prevPers->setPersonnel($item->getPersonnel());
        $prevPers->setIdPersonnel($item->getPersonnel()->getId());
        $prevPers->setStructureAnneeUniversitaire($item->getAnneeUniversitaire());
        $prevPers->setStatut($item->getPersonnel()->getStatut()->getLibelle());
        $prevPers->setIdEnseignement($item->getEnseignement()->getId());
        $prevPers->setCodeEnseignement($item->getEnseignement()->getCodeEnseignement());
        $prevPers->setLibelleEnseignement($item->getEnseignement()->getDisplay());
        $prevPers->setTypeEnseignement($item->getEnseignement()->getType());
        $prevPers->setLibelle($item->getPersonnel()->getDisplay());
        $prevPers->setHeures([
            'CM' => [
                'NbHrGrp' => round($item->getGroupes()['CM'] !== 0 ? $item->getHeures()['CM'] / $item->getGroupes()['CM'] : $item->getHeures()['CM'], 1),
                'NbGrp' => $item->getGroupes()['CM'],
                'NbSeanceGrp' => round((($item->getGroupes()['CM'] !== 0 ? $item->getHeures()['CM'] / $item->getGroupes()['CM'] : $item->getHeures()['CM']) / $item::DUREE_SEANCE) * $item->getGroupes()['CM'], 1),
            ],
            'TD' => [
                'NbHrGrp' => round($item->getGroupes()['TD'] !== 0 ? $item->getHeures()['TD'] / $item->getGroupes()['TD'] : $item->getHeures()['TD'], 1),
                'NbGrp' => $item->getGroupes()['TD'],
                'NbSeanceGrp' => round((($item->getGroupes()['TD'] !== 0 ? $item->getHeures()['TD'] / $item->getGroupes()['TD'] : $item->getHeures()['TD']) / $item::DUREE_SEANCE) * $item->getGroupes()['TD'], 1),
            ],
            'TP' => [
                'NbHrGrp' => round($item->getGroupes()['TP'] !== 0 ? $item->getHeures()['TP'] / $item->getGroupes()['TP'] : $item->getHeures()['TP'], 1),
                'NbGrp' => $item->getGroupes()['TP'],
                'NbSeanceGrp' => round((($item->getGroupes()['TP'] !== 0 ? $item->getHeures()['TP'] / $item->getGroupes()['TP'] : $item->getHeures()['TP']) / $item::DUREE_SEANCE) * $item->getGroupes()['TP'], 1),
            ],
            'Projet' => [
                'NbHrGrp' => round($item->getHeures()['Projet'], 1),
                'NbGrp' => $item->getGroupes()['Projet'],
                'NbSeanceGrp' => round($item->getHeures()['Projet'] / $item::DUREE_SEANCE, 1),
            ],
        ]);
        $prevPers->setGroupes($item->getGroupes());

        return $prevPers;
    }
}
