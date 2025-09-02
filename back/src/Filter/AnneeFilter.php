<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

#[ApiFilter(AnneeFilter::class)]
class AnneeFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (null === $value) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        if ('departement' === $property) {
            $queryBuilder
                ->join("$alias.pn", "pn")
                ->join("pn.diplome", "d")
                ->join("d.departement", "departement")
                ->andWhere("departement.id = :departement")
                ->setParameter("departement", $value);
        } elseif ('pn' === $property) {
            $queryBuilder
                ->join("$alias.pn", "pn")
                ->andWhere("pn.id = :pn")
                ->setParameter("pn", $value);
        } elseif ('diplome' === $property) {
            $queryBuilder
                ->join("$alias.pn", "pn")
                ->join("pn.diplome", "d")
                ->andWhere("pn.actif = true")
                ->andWhere("d.id = :diplome")
                ->setParameter("diplome", $value);
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
            'pn' => [
                'property' => 'pn',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by pn',
                ],
            ],
            'diplome' => [
                'property' => 'diplome',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by diploma',
                ],
            ],
        ];
    }
}
