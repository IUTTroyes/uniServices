<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

#[ApiFilter(DiplomeFilter::class)]
class DiplomeFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (null === $value) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        if ('departement' === $property) {
            $queryBuilder
                ->andWhere(sprintf('%s.departement = :departement', $alias))
                ->setParameter('departement', $value)
            ;
        }
        elseif ('actif' === $property) {
            $queryBuilder
                ->andWhere(sprintf('%s.actif = :actif', $alias))
                ->setParameter('actif', filter_var($value, FILTER_VALIDATE_BOOLEAN))
            ;
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'departement' => [
                'property' => 'departement',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by departement',
                ],
            ],
            'actif' => [
                'property' => 'actif',
                'type' => Type::BUILTIN_TYPE_BOOL,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by actif status',
                ],
            ],
        ];
    }
}
