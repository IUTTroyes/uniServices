<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Operation;
use App\Entity\Structure\StructureDepartementPersonnel;
use Doctrine\ORM\QueryBuilder;

#[ApiFilter(DepartementFilter::class)]
class DepartementFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (null === $value) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        if ('personnel' === $property) {
            $queryBuilder
                ->innerJoin(sprintf('%s.departementPersonnels', $alias), 'sdp')
                ->andWhere('sdp.personnel = :personnel')
                ->setParameter('personnel', (int) $value)
                ->orderBy(sprintf('%s.libelle', $alias), 'ASC')
            ;
        } elseif ('actif' === $property) {
            $queryBuilder
                ->andWhere(sprintf('%s.actif = :actif', $alias))
                ->setParameter('actif', $value)
            ;
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'personnel' => [
                'property' => 'personnel',
                'type' => 'int',
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by personnel',
                ],
            ],
            'actif' => [
                'property' => 'actif',
                'type' => 'bool',
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by active status',
                ],
            ],
        ];
    }
}
