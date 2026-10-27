<?php

namespace App\State\Provider\Personnel;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use App\Security\UserEffectivePermissionService;

class PersonnelConfigProvider implements ProviderInterface
{
    public function __construct(
        private CollectionProvider $collectionProvider,
        private ItemProvider $itemProvider,
        private UserEffectivePermissionService $effectivePermissionService,

    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof GetCollection) {
            $data = $this->collectionProvider->provide($operation, $uriVariables, $context);

            if ($data instanceof PaginatorInterface) {
                $items = [];
                foreach ($data as $item) {
                    $items[] = $this->formatPersonnel($item);
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
                $items[] = $this->formatPersonnel($item);
            }

            // récupérer le nombre total d'éléments sans la pagination
            $items['nbPersonnels'] = count($items);

            return $items;
        }

        return $this->itemProvider->provide($operation, $uriVariables, $context);
    }

    public function formatPersonnel($item): array
    {
        $departements = [];
        $packages = [];
        $permissions = [];

        foreach ($item->getDepartementsPersonnel() as $departementPersonnel) {
            $departement = $departementPersonnel->getDepartement();
            $entry = [
                'id' => $departementPersonnel->getId(),
                'departementId' => $departement?->getId(),
                'libelle' => $departement?->getLibelle(),
                'defaut' => $departementPersonnel->isDefaut(),
                'affectation' => $departementPersonnel->isAffectation(),
                'packages' => array_values(array_unique(array_map('strval', $departementPersonnel->getPackages()))),
                'responsableParcoursDiplomes' => array_values(array_map(static fn ($diplome) => '/api/structure_diplomes/'.$diplome->getId(), $departementPersonnel->getResponsableParcoursDiplomes()->toArray())),
                'diplomes' => array_map(static fn ($diplome) => ['id' => $diplome->getId(), 'libelle' => $diplome->getLibelle(), 'iri' => '/api/structure_diplomes/'.$diplome->getId()], $departement?->getStructureDiplomes()->toArray() ?? []),
                'permissions' => array_values(array_unique(array_map('strval', $departementPersonnel->getPermissions()))),
            ];

            $departements[] = $entry;

            foreach ($entry['packages'] as $package) {
                $packages[$package] = true;
            }

            foreach ($entry['permissions'] as $permission) {
                $permissions[$permission] = true;
            }
        }

        return [
            'id' => $item->getId(),
            'nom' => $item->getNom(),
            'prenom' => $item->getPrenom(),
            'username' => $item->getUsername(),
            'mailUniv' => $item->getMailUniv(),
            'photoName' => $item->getPhotoName(),
            'roles' => $this->effectivePermissionService->getEffectivePermissions($item),
            'departements' => $departements,
            'packages' => array_keys($packages),
            'permissions' => array_keys($permissions),
            'departementCount' => count($departements),
            'numeroHarpege' => $item->getNumeroHarpege(),
        ];
    }
}
