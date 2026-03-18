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
        elseif ('anneeUniversitaire' === $property) {
            $queryBuilder
                ->join(sprintf('%s.pns', $alias), 'pn')
                ->join('pn.anneeUniversitaire', 'anneeUniv')
                ->andWhere('anneeUniv.id = :anneeUniversitaire')
                ->setParameter('anneeUniversitaire', $value)
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
            'anneeUniversitaire' => [
                'property' => 'anneeUniversitaire',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by année universitaire',
                ],
            ],
        ];
    }
}
