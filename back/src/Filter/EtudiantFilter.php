<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

#[ApiFilter(EtudiantFilter::class)]
class EtudiantFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (null === $value) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        if ('departement' === $property) {
            $queryBuilder
                ->join(sprintf('%s.scolarites', $alias), 'scolarites')
                ->join('scolarites.departement', 'departement')
                ->andWhere('departement.id = :departement')
                ->setParameter('departement', $value);
        }

        if ('anneeUniversitaire' === $property) {
            $queryBuilder
                ->join(sprintf('%s.scolarites', $alias), 'scolarites')
                ->join('scolarites.anneeUniversitaire', 'anneeUniversitaire')
                ->andWhere('anneeUniversitaire.id = :anneeUniversitaire')
                ->setParameter('anneeUniversitaire', $value);

            if (isset($context['filters']['annee'])) {
                $queryBuilder
                    ->join('scolarites.annee', 'annee')
                    ->andWhere('annee.id = :annee')
                    ->setParameter('annee', $context['filters']['annee']);
            }
        }

        if ('semestre' === $property) {
            $queryBuilder
                ->join(sprintf('%s.scolarites', $alias), 'scolarites')
                ->join('scolarites.semestre', 'scolariteSemestre')
                ->join('scolariteSemestre.semestre', 'semestre')
                ->andWhere('semestre.id = :semestre')
                ->setParameter('semestre', $value);
        }

        if ('annee' === $property) {
            $queryBuilder
                ->join(sprintf('%s.scolarites', $alias), 'scolarites')
                ->join('scolarites.annee', 'annee')
                ->andWhere('annee.id = :annee')
                ->andWhere('scolarites.actif = true')
                ->setParameter('annee', $value);
        }

        if ('groupe' === $property) {
            $queryBuilder
                ->join(sprintf('%s.scolarites', $alias), 'scolarites')
                ->join('scolarites.scolariteSemestre', 'scolariteSemestre')
                ->join('scolariteSemestre.groupes', 'groupe')
                ->andWhere('groupe.id = :groupe')
                ->setParameter('groupe', $value);
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
                    'description' => 'Filter by anneeUniversitaire',
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
            'annee' => [
                'property' => 'annee',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by annee',
                ],
            ],
            'groupe' => [
                'property' => 'groupe',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by groupe',
                ],
            ],
        ];
    }
}
