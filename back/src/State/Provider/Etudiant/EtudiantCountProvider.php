<?php

namespace App\State\Provider\Etudiant;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\ProviderInterface;

class EtudiantCountProvider implements ProviderInterface
{
    public function __construct(
        private CollectionProvider $collectionProvider,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        // On force une GetCollection pour que le CollectionProvider fonctionne
        // mais on désactive la pagination pour tout compter
        $countOperation = (new GetCollection())
            ->withClass($operation->getClass())
            ->withFilters($operation->getFilters() ?? []);

        $context['filters'] = $context['filters'] ?? [];

        $data = $this->collectionProvider->provide($countOperation, $uriVariables, $context);

        if ($data instanceof PaginatorInterface) {
            return ['total' => (int) $data->getTotalItems()];
        }

        return ['total' => count(iterator_to_array($data))];
    }
}
