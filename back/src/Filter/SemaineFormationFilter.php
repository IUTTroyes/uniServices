<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use ApiPlatform\Metadata\ApiFilter;
use Symfony\Component\PropertyInfo\Type;

#[ApiFilter(SemaineFormationFilter::class)]
class SemaineFormationFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if ('semaineFormation' !== $property || null === $value) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->andWhere(sprintf('%s.semaineFormation = :semaineFormation', $alias))
            ->setParameter('semaineFormation', $value)
            ->setMaxResults(1)
        ;
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'semaineFormation' => [
                'property' => 'semaineFormation',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by semaineFormation',
                ],
            ],
        ];
    }
}
