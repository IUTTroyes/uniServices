<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

#[ApiFilter(DepartementPersonnelFilter::class)]
class DepartementPersonnelFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (null === $value) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        if ('departement' === $property) {
            $departementAlias = $queryNameGenerator->generateJoinAlias('departement');
            $queryBuilder
                ->join("$alias.departement", $departementAlias)
                ->andWhere("$departementAlias.id = :departementId")
                ->setParameter('departementId', $value);
        }
        if ('personnel' === $property) {
            $personnelAlias = $queryNameGenerator->generateJoinAlias('personnel');
            $queryBuilder
                ->join("$alias.personnel", $personnelAlias)
                ->andWhere("$personnelAlias.id = :personnelId")
                ->setParameter('personnelId', $value);
        }
        if ('structureDepartementPersonnelId' === $property) {
            $queryBuilder
                ->where("$alias.id = :structureDepartementPersonnelId")
                ->setParameter('structureDepartementPersonnelId', $value);
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
            'personnel' => [
                'property' => 'departement',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by personnel',
                ],
            ],
        ];
    }

    /**
     * Reuse an existing join alias if present, otherwise create a new join and return its alias.
     * This prevents multiple joins to the same association with different aliases.
     */
    private function getOrCreateJoin(QueryBuilder $qb, QueryNameGeneratorInterface $queryNameGenerator, string $fromAlias, string $association): string
    {
        // Try to find an existing join for the association
        foreach ($qb->getDQLPart('join') as $alias => $joins) {
            foreach ($joins as $join) {
                if ($join->getJoin() === "$fromAlias.$association") {
                    return $join->getAlias();
                }
            }
        }

        // If not found, generate a new alias and add the join
        $newAlias = $queryNameGenerator->generateJoinAlias($association);
        $qb->join("$fromAlias.$association", $newAlias);
        return $newAlias;
    }
}
