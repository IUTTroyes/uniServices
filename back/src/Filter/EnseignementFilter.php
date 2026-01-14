<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

#[ApiFilter(EnseignementFilter::class)]
class EnseignementFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (null === $value) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        if ('semestre' === $property) {
            $queryBuilder
                ->join("$alias.enseignementUes", "enseignementUe")
                ->join("enseignementUe.ue", "ue")
                ->andWhere("ue.semestre = :semestre")
                ->setParameter("semestre", $value);
            ;
        }

        if ('annee' === $property) {
            $queryBuilder
                ->join("$alias.enseignementUes", "enseignementUe2")
                ->join("enseignementUe2.ue", "ue2")
                ->join("ue2.semestre", "semestre2")
                ->andWhere("semestre2.annee = :annee")
                ->setParameter("annee", $value);
        }

        if ('departement' === $property || ('departement' === $property && 'actif' === $property)) {
            $queryBuilder
                ->join("$alias.enseignementUes", "enseignementUe")
                ->join("enseignementUe.ue", "ue")
                ->join("ue.semestre", "semestre")
                ->join("semestre.annee" , "annee")
                ->join("annee.pn", "pn")
                ->join("pn.anneeUniversitaire", "anneeUniversitaire")
                ->join("pn.diplome", "diplome")
                ->join("diplome.departement", "departement")
                ->andWhere("departement.id = :departement")
                ->andWhere("anneeUniversitaire.actif = true")
                ->andWhere("diplome.actif = true")
                ->setParameter("departement", $value);
        }

        if ('ue' === $property) {
            $queryBuilder
                ->join("$alias.enseignementUes", "enseignementUe")
                ->join("enseignementUe.ue", "ue")
                ->andWhere("ue.id = :ue")
                ->setParameter("ue", $value);
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'semestre' => [
                'property' => 'semestre',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by semestre',
                ],
            ],
            'departement' => [
                'property' => 'departement',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by departement',
                ],
            ],
            'ue' => [
                'property' => 'ue',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by UE',
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
            'annee' => [
                'property' => 'annee',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by année',
                ],
            ]
        ];
    }
}
