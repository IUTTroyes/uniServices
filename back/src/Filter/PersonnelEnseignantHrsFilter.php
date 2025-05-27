<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

#[ApiFilter(PersonnelEnseignantHrsFilter::class)]
class PersonnelEnseignantHrsFilter extends AbstractFilter
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

        if ('personnel' === $property) {
            $queryBuilder
                ->join("$alias.personnel", 'personnel')
                ->andWhere('personnel.id = :personnel')
                ->setParameter("personnel", $value);
        }
        if ('annee_universitaire' === $property) {
            $queryBuilder
                ->join("$alias.annee_universitaire", 'annee_universitaire')
                ->andWhere('annee_universitaire.id = :annee_universitaire')
                ->setParameter("annee_universitaire", $value);
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'personnel' => [
                'property' => 'personnel',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by personnel',
                ],
            ],
            'annee_universitaire' => [
                'property' => 'annee_universitaire',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by academic year',
                ],
            ],
        ];
    }
}
