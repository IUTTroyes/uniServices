<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Operation;
use App\Entity\Structure\StructureAnnee;
use Doctrine\ORM\QueryBuilder;

#[ApiFilter(GroupeFilter::class)]
class GroupeFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (null === $value) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        if ('semestre' === $property) {
            $queryBuilder
                ->leftJoin(sprintf('%s.semestres', $alias), 'semestre')
                ->andWhere('semestre.id = :semestre')
                ->setParameter('semestre', $value)
            ;
        } elseif ('type' === $property) {
            $queryBuilder
                ->andWhere(sprintf('%s.type = :type', $alias))
                ->setParameter('type', $value)
            ;
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'semestre' => [
                'property' => 'semestre',
                'type' => 'int',
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by semestre',
                ],
            ],
            'type' => [
                'property' => 'type',
                'type' => 'string',
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by type (e.g., TD, TP, CM)',
                ],
            ],
        ];
    }
}
