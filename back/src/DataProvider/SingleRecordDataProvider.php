<?php

namespace App\DataProvider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Structure\StructureCalendrier;
use Doctrine\ORM\EntityManagerInterface;

class SingleRecordDataProvider implements ProviderInterface
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?StructureCalendrier
    {
        $repository = $this->entityManager->getRepository(StructureCalendrier::class);
        $criteria = $context['filters'] ?? [];

        return $repository->findOneBy($criteria);
    }
}
