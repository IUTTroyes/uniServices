<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

#[ApiFilter(PersonnelFilter::class)]
class PersonnelFilter extends AbstractFilter
{
    protected function filterProperty(
        string $property,
               $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if (null === $value) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        if ('departement' === $property) {
            $departementPersonnelAlias = $queryNameGenerator->generateJoinAlias('departementPersonnels');
            $departementAlias = $queryNameGenerator->generateJoinAlias('departement');

            $queryBuilder
                ->leftJoin(sprintf('%s.departementPersonnels', $alias), $departementPersonnelAlias)
                ->leftJoin(sprintf('%s.departement', $departementPersonnelAlias), $departementAlias)
                ->andWhere(sprintf('%s.id = :departement', $departementAlias))
                ->setParameter('departement', $value);
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
        ];
    }
}
