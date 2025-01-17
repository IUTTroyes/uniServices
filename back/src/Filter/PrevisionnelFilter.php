<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Structure\StructureAnnee;
use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructureSemestre;
use Doctrine\ORM\QueryBuilder;
use ApiPlatform\Metadata\ApiFilter;
use Symfony\Component\PropertyInfo\Type;

#[ApiFilter(PrevisionnelFilter::class)]
class PrevisionnelFilter extends AbstractFilter
{
    private const FILTERS = [
        'personnel' => 'personnel',
        'anneeUniversitaire' => 'anneeUniversitaire',
        'departement' => 'departement',
        'diplome' => 'diplome',
        'semestre' => 'semestre',
    ];
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (!in_array($property, self::FILTERS) || null === $value) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        if ('personnel' === $property) {
            $queryBuilder
                ->andWhere(sprintf('%s.personnel = :personnel', $alias))
                ->setParameter('personnel', $value)
            ;
        }

        if ('anneeUniversitaire' === $property) {
            $queryBuilder
                ->andWhere(sprintf('%s.anneeUniversitaire = :anneeUniversitaire', $alias))
                ->setParameter('anneeUniversitaire', $value)
            ;
        }

        if ('semestre' === $property) {
            $queryBuilder
                ->andWhere(sprintf('%s.semestre = :semestre', $alias))
                ->setParameter('semestre', $value)
            ;
        }

        if ('diplome' === $property) {
            $queryBuilder
                ->innerJoin(StructureSemestre::class, 'ss', 'WITH', sprintf('%s.semestre = ss.id', $alias))
                ->innerJoin(StructureAnnee::class, 'sa', 'WITH', 'ss.annee = sa.id')
                ->andWhere('sa.structureDiplome = :diplome')
                ->setParameter('diplome', $value)
            ;
        }

        if ('departement' === $property) {
            $queryBuilder
                ->innerJoin(StructureSemestre::class, 'ss', 'WITH', sprintf('%s.semestre = ss.id', $alias))
                ->innerJoin(StructureAnnee::class, 'sa', 'WITH', 'ss.annee = sa.id')
                ->innerJoin(StructureDiplome::class, 'sd', 'WITH', 'sa.structureDiplome = sd.id')
                ->innerJoin(StructureDepartement::class, 'sde', 'WITH', 'sd.departement = sde.id')
                ->andWhere('sde.id = :departement')
                ->setParameter('departement', $value)
            ;
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
            'anneeUniversitaire' => [
                'property' => 'anneeUniversitaire',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by anneeUniversitaire',
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
            'diplome' => [
                'property' => 'diplome',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by diplome',
                ],
            ],
            'semestre' => [
                'property' => 'semestre',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by semestre',
                ],
            ]
        ];
    }
}
