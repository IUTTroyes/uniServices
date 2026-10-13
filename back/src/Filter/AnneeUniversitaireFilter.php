<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

#[ApiFilter(AnneeUniversitaireFilter::class)]
class AnneeUniversitaireFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (null === $value) {
            return;
        }
        $alias = $queryBuilder->getRootAliases()[0];

        // Use the query name generator and reuse existing joins when possible
        if ('since' === $property) {
            // récupérer celle dont annee universitaire est supérieur ou égal à la valeur de since
            $queryBuilder
                ->andWhere(sprintf('%s.annee >= :since', $alias))
                ->setParameter('since', $value);
        }
        if ('until' === $property) {
            // récupérer celle dont annee universitaire est inférieur ou égal à la valeur de until
            $queryBuilder
                ->andWhere(sprintf('%s.annee <= :until', $alias))
                ->setParameter('until', $value);
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'since' => [
                'property' => 'since',
                'type' => 'int',
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by since',
                ],
            ],
            'until' => [
                'property' => 'until',
                'type' => 'int',
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by until',
                ],
            ],

        ];
    }
}
