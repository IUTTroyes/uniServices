<?php

namespace App\State\Previsionnel;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiDto\Previsionnel\PrevisionnelPersonnelDto;
use App\Repository\Structure\StructureDepartementPersonnelRepository;

class PrevisionnelPersonnelProvider implements ProviderInterface
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

            if (empty($data)) {
                return [];
            }

            $output = [];

            $totalCM = 0;
            $totalTD = 0;
            $totalTP = 0;
            $totalProjet = 0;
            $total = 0;

            foreach ($data as $item) {

                $output['previ'][] = $this->toDto($item);

                $totalCM += $item->getHeures()['CM'] ?? 0;
                $totalTD += $item->getHeures()['TD'] ?? 0;
                $totalTP += $item->getHeures()['TP'] ?? 0;
                $totalProjet += $item->getHeures()['Projet'] ?? 0;
            }

            $output['total'] = [
                'CM' => $totalCM,
                'TD' => $totalTD,
                'TP' => $totalTP,
                'Projet' => $totalProjet,
                'Total' => $totalCM + $totalTD + $totalTP,
            ];

            $total = $totalCM + $totalTD + $totalTP;
            $total = round($total, 2);

            $departement = $this->structureDepartementPersonnelRepository->findOneBy(['personnel' => $item->getPersonnel()->getId(), 'departement' => $context['filters']['departement']]);
            $departementAffectation = $this->structureDepartementPersonnelRepository->findOneByPersonnelAffectation($item->getPersonnel()->getId());

            if ($departementAffectation && $departement->getId() === $departementAffectation->getId() || $item->getPersonnel()->getStatut()->getLibelle() === 'Enseignant Vacataire') {
                $nbHeuresService = $item->getPersonnel()->getNbHeuresService();
                $affectation = true;
            } else {
                $nbHeuresService = 'Service réalisé dans un autre département';
                $affectation = false;
            }
            $statutLibelle = $item->getPersonnel()->getStatut()->getLibelle();

            if ($statutLibelle === 'Enseignant Vacataire' && $total < $nbHeuresService) {
                $diff = 'Peut rester '.($total - $nbHeuresService);
                // enlever le signe négatif si le nombre est négatif
                $diff = str_replace('-', '', $diff);
            } elseif ($statutLibelle === 'Enseignant Vacataire' && $total > $nbHeuresService) {
                $diff = 'Dépassement de '.($total - $nbHeuresService);
            } else {
                if ($affectation) {
                    $diff = $total - $nbHeuresService;
                } else {
                    $diff = 'Service réalisé dans un autre département';
                }
            }

            $output['totalEquTd'] = [
                'TotalClassique' => $totalCM + $totalTD + $totalTP,
                'TotalTd' => $totalCM * $item->getEnseignement()::MAJORATION_CM + $totalTD + $totalTP,
                'Service' => $item->getPersonnel()->getNbHeuresService(),
                'Diff' => $diff,
            ];

            $output['personnelDatas'] = [
                'statut' => $item->getPersonnel()->getStatut(),
                'statutSeverity' => $item->getPersonnel()->getStatut()->getBadge(),
            ];

            return $output;
        } else {
            $data = $this->itemProvider->provide($operation, $uriVariables, $context);
        }

        return $this->toDto($data);
    }

    public function toDto($item)
    {
        $prevEnseignant = new PrevisionnelPersonnelDto();
        $prevEnseignant->setId($item->getId());
        $prevEnseignant->setIdEnseignement($item->getEnseignement()->getId());
        $prevEnseignant->setLibelle($item->getEnseignement()->getLibelle());
        $prevEnseignant->setLibelleEnseignement($item->getEnseignement()->getDisplay());
        $prevEnseignant->setIdPersonnel($item->getPersonnel()->getId());
        $prevEnseignant->setPersonnel($item->getPersonnel());
        $prevEnseignant->setHeures(
            [
                'CM' => $item->getHeures()['CM'] ?? 0,
                'TD' => $item->getHeures()['TD'] ?? 0,
                'TP' => $item->getHeures()['TP'] ?? 0,
                'Projet' => $item->getHeures()['Projet'] ?? 0,
            ]
        );
        $prevEnseignant->setGroupes(
            [
                'CM' => $item->getGroupes()['CM'] ?? 0,
                'TD' => $item->getGroupes()['TD'] ?? 0,
                'TP' => $item->getGroupes()['TP'] ?? 0,
                'Projet' => $item->getGroupes()['Projet'] ?? 0,
            ]
        );

        return $prevEnseignant;
    }
}
