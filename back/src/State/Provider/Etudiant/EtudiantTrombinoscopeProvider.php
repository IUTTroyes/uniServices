<?php

namespace App\State\Etudiant;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use App\ApiDto\EtudiantScolariteSemestre\EtudiantScolariteSemestreDto;

class EtudiantTrombinoscopeProvider implements ProviderInterface
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

            // Si Api Platform fournit un paginator, on conserve les métadonnées de pagination
            if ($data instanceof PaginatorInterface) {
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
                    $groupesArray = [];
                    foreach ($groupes as $groupe) {
                        $groupesArray[] = [
                            'id' => $groupe->getId(),
                            'libelle' => $groupe->getLibelle(),
                            'type' => $groupe->getType(),
                        ];
                    }
                    $dto->setGroupes($groupesArray);

                    $dto->setId($item->getId());

                    $items[] = $dto;
                }

                // Tri alphabétique par nom d'étudiant (insensible à la casse)
                usort($items, function ($a, $b) {
                    $na = '';
                    $nb = '';
                    if (method_exists($a, 'getEtudiant')) {
                        $ea = $a->getEtudiant();
                        $na = is_array($ea) && isset($ea['nom']) ? mb_strtolower((string) $ea['nom']) : '';
                    }
                    if (method_exists($b, 'getEtudiant')) {
                        $eb = $b->getEtudiant();
                        $nb = is_array($eb) && isset($eb['nom']) ? mb_strtolower((string) $eb['nom']) : '';
                    }
                    return strcasecmp($na, $nb);
                });

                return new TraversablePaginator(
                    new \ArrayIterator($items),
                    $data->getCurrentPage(),
                    $data->getItemsPerPage(),
                    $data->getTotalItems()
                );
            }

            // Cas non paginé (pagination désactivée) : renvoyer la liste de DTO
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
                $groupesArray = [];
                foreach ($groupes as $groupe) {
                    $groupesArray[] = [
                        'id' => $groupe->getId(),
                        'libelle' => $groupe->getLibelle(),
                        'type' => $groupe->getType(),
                    ];
                }
                $dto->setGroupes($groupesArray);

                $dto->setId($item->getId());

                $items[] = $dto;
            }

            // Tri alphabétique par nom d'étudiant (insensible à la casse)
            usort($items, function ($a, $b) {
                $na = '';
                $nb = '';
                if (method_exists($a, 'getEtudiant')) {
                    $ea = $a->getEtudiant();
                    $na = is_array($ea) && isset($ea['nom']) ? mb_strtolower((string) $ea['nom']) : '';
                }
                if (method_exists($b, 'getEtudiant')) {
                    $eb = $b->getEtudiant();
                    $nb = is_array($eb) && isset($eb['nom']) ? mb_strtolower((string) $eb['nom']) : '';
                }
                return strcasecmp($na, $nb);
            });

            return $items;
        }

        // Cas item: on effectue un simple pass-through également
        return $this->itemProvider->provide($operation, $uriVariables, $context);
    }
}
