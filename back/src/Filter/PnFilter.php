<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

class PnFilter extends AbstractFilter
{
    private const FILTERS = [
        'personnel' => 'personnel',
        'anneeUniversitaire' => 'anneeUniversitaire',
        'departement' => 'departement',
        'semestre' => 'semestre',
        'enseignement' => 'enseignement',
        'diplome' => 'diplome',
    ];
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (!in_array($property, self::FILTERS) || null === $value) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        if ('diplome' === $property) {
            $queryBuilder
                ->join(sprintf('%s.diplome', $alias), 'diplome')
                ->andWhere('diplome.id = :diplome')
                ->setParameter('diplome', $value)
            ;
        }

        if ('anneeUniversitaire' === $property) {
            $queryBuilder
                ->join("$alias.anneeUniversitaire", "anneeUniversitaire")
                ->andWhere("anneeUniversitaire.id = :anneeUniversitaire")
                ->setParameter("anneeUniversitaire", $value)
            ;
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'diplome' => [
                'property' => 'diplome',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by diplôme',
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
