<?php

namespace App\State\EtudiantScolariteSemestre;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiDto\EtudiantScolariteSemestre\EtudiantScolariteSemestreDto;
use DateTime;
use App\ApiDto\Edt\EdtStatsDto;

class EtudiantScolariteSemestreProvider implements ProviderInterface
{

    public function __construct(
        private CollectionProvider $collectionProvider,
        private ItemProvider $itemProvider,
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof GetCollection) {
            $data = $this->collectionProvider->provide($operation, $uriVariables, $context);

            if (empty($data)) {
                // Renvoie un DTO vide
                return new EdtStatsDto();
            }

            $items = [];
            foreach ($data as $item) {
                $dto = new EtudiantScolariteSemestreDto();

                $etudiant = $item->getScolarite()?->getEtudiant();
                if ($etudiant) {
                    $dto->setEtudiant([
                        'id' => $etudiant->getId(),
                        'prenom' => $etudiant->getPrenom(),
                        'nom' => $etudiant->getNom(),
                        'numEtudiant' => $etudiant->getNumEtudiant(),
                        'bac' => $etudiant->getBac()?->getLibelle(),
                    ]);
                } else {
                    $dto->setEtudiant([]);
                }

                $groupes = $item->getGroupes();
                if ($groupes) {
                    $groupesArray = [];
                    foreach ($groupes as $groupe) {
                        $groupesArray[] = [
                            'id' => $groupe->getId(),
                            'libelle' => $groupe->getLibelle(),
                            'type' => $groupe->getType(),
                        ];
                    }
                    $dto->setGroupes($groupesArray);
                } else {
                    $dto->setGroupes([]);
                }

                $dto->setId($item->getId());

                $items[] = $dto;
            }

            return $items;
        }

        // Cas item: on effectue un simple pass-through également
        return $this->itemProvider->provide($operation, $uriVariables, $context);
    }
}
