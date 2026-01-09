<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

#[ApiFilter(EtudiantScolariteSemestreFilter::class)]
class EtudiantScolariteSemestreFilter extends AbstractFilter
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

        if ('scolarite' === $property) {
            $queryBuilder
                ->join("$alias.scolarite", 'scolarite')
                ->andWhere('scolarite.id = :scolarite')
                ->setParameter("scolarite", $value);
        }

        if ('semestre' === $property) {
            $queryBuilder
                ->join("$alias.semestre", 'semestre')
                ->andWhere('semestre.id = :semestre')
                ->setParameter("semestre", $value);
        }

        if ('etudiant' === $property) {
            $queryBuilder
                ->join("$alias.scolarite", 'scolarite')
                ->join('scolarite.etudiant', 'etudiant')
                ->andWhere('etudiant.id = :etudiant')
                ->setParameter("etudiant", $value);
        }

        if ('anneeUniversitaire' === $property) {
            $queryBuilder
                ->join("$alias.scolarite", 'scolarite')
                ->andWhere('scolarite.anneeUniversitaire = :anneeUniversitaire')
                ->setParameter("anneeUniversitaire", $value);
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'scolarite' => [
                'property' => 'scolarite',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by scolarite',
                ],
            ],
            'semestre' => [
                'property' => 'semestre',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by semestre',
                ],
            ],
            'etudiant' => [
                'property' => 'etudiant',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by etudiant',
                ],
            ],
            'anneeUniversitaire' => [
                'property' => 'anneeUniversitaire',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by anneeUniversitaire',
                ],
            ]
        ];
    }
}
