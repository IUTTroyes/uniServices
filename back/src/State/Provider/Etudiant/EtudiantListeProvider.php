<?php

namespace App\State\Provider\Etudiant;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Etudiant\EtudiantScolarite;

class EtudiantListeProvider implements ProviderInterface
{
    public function __construct(
        private CollectionProvider $collectionProvider,
        private ItemProvider $itemProvider,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof GetCollection) {
            $data = $this->collectionProvider->provide($operation, $uriVariables, $context);

            if ($data instanceof PaginatorInterface) {
                $items = [];
                foreach ($data as $item) {
                    $items[] = $this->formatScolarite($item);
                }

                return new TraversablePaginator(
                    new \ArrayIterator($items),
                    $data->getCurrentPage(),
                    $data->getItemsPerPage(),
                    $data->getTotalItems()
                );
            }

            $items = [];
            foreach ($data as $item) {
                $items[] = $this->formatScolarite($item);
            }

            return $items;
        }

        return $this->itemProvider->provide($operation, $uriVariables, $context);
    }

    private function formatScolarite(EtudiantScolarite $scolarite): array
    {
        $etudiant = $scolarite->getEtudiant();
        $annees = [];

        foreach ($scolarite->getAnnee() as $annee) {
            $annees[] = [
                'id' => $annee->getId(),
                'libelle' => $annee->getLibelle(),
            ];
        }

        return [
            'id' => $scolarite->getId(),
            'etudiant' => $etudiant ? [
                'id' => $etudiant->getId(),
                'prenom' => $etudiant->getPrenom(),
                'nom' => $etudiant->getNom(),
                'mailUniv' => $etudiant->getMailUniv(),
                'numEtudiant' => $etudiant->getNumEtudiant(),
            ] : [],
            'annee' => $annees,
        ];
    }
}
