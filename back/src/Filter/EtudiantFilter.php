<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

#[ApiFilter(EtudiantFilter::class)]
class EtudiantFilter extends AbstractFilter
{
    protected function filterProperty(string $property, mixed $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (null === $value) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        if ('departement' === $property) {
            $scolaritesAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $alias, 'scolarites');
            $deptAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $scolaritesAlias, 'departement');
            $queryBuilder
                ->andWhere(sprintf('%s.id = :departement', $deptAlias))
                ->setParameter('departement', $value);
        }

        if ('anneeUniversitaire' === $property) {
            $scolaritesAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $alias, 'scolarites');
            $anneeUnivAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $scolaritesAlias, 'anneeUniversitaire');
            $queryBuilder
                ->andWhere(sprintf('%s.id = :anneeUniversitaire', $anneeUnivAlias))
                ->setParameter('anneeUniversitaire', $value);

            if (isset($context['filters']['annee'])) {
                $anneeAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $scolaritesAlias, 'annee');
                $queryBuilder
                    ->andWhere(sprintf('%s.id = :annee', $anneeAlias))
                    ->setParameter('annee', $context['filters']['annee']);
            }
        }

        if ('semestre' === $property) {
            $scolaritesAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $alias, 'scolarites');
            $scolariteSemestreAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $scolaritesAlias, 'scolariteSemestre');
            $semestreAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $scolariteSemestreAlias, 'semestre');
            $queryBuilder
                ->andWhere(sprintf('%s.id = :semestre', $semestreAlias))
                ->setParameter('semestre', $value);
        }

        if ('annee' === $property) {
            $scolaritesAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $alias, 'scolarites');
            $anneeAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $scolaritesAlias, 'annee');
            $queryBuilder
                ->andWhere(sprintf('%s.id = :annee', $anneeAlias))
                ->andWhere(sprintf('%s.actif = true', $scolaritesAlias))
                ->setParameter('annee', $value);
        }

        if ('groupe' === $property) {
            $scolaritesAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $alias, 'scolarites');
            $scolariteSemestreAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $scolaritesAlias, 'scolariteSemestre');
            $groupeAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $scolariteSemestreAlias, 'groupes');
            $queryBuilder
                ->andWhere(sprintf('%s.id = :groupe', $groupeAlias))
                ->setParameter('groupe', $value);
        }

        if ('sansScolarite' === $property && filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
            $queryBuilder
                ->leftJoin(sprintf('%s.scolarites', $alias), 'scolaritesSans')
                ->andWhere('scolaritesSans.id IS NULL');
        }
        if ('actif' === $property) {
            $scolaritesAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $alias, 'scolarites');
            $queryBuilder
                ->andWhere(sprintf('%s.actif = :actif', $scolaritesAlias))
                ->setParameter('actif', filter_var($value, FILTER_VALIDATE_BOOLEAN));
        }

        if ('anneeSortie' === $property || 'annee_sortie' === $property) {
            $queryBuilder
                ->andWhere(sprintf('%s.annee_sortie = :anneeSortie', $alias))
                ->setParameter('anneeSortie', (int) $value);
        }
    }

    private function getOrCreateJoin(QueryBuilder $qb, QueryNameGeneratorInterface $queryNameGenerator, string $fromAlias, string $association): string
    {
        foreach ($qb->getDQLPart('join') as $joins) {
            foreach ($joins as $join) {
                if ($join->getJoin() === "$fromAlias.$association") {
                    return $join->getAlias();
                }
            }
        }

        $newAlias = $queryNameGenerator->generateJoinAlias($association);
        $qb->join("$fromAlias.$association", $newAlias);
        return $newAlias;
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'departement' => [
                'property' => 'departement',
                'type' => 'int',
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by departement',
                ],
            ],
            'anneeUniversitaire' => [
                'property' => 'anneeUniversitaire',
                'type' => 'int',
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by anneeUniversitaire',
                ],
            ],
            'semestre' => [
                'property' => 'semestre',
                'type' => 'int',
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by semestre',
                ],
            ],
            'annee' => [
                'property' => 'annee',
                'type' => 'int',
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by annee',
                ],
            ],
            'groupe' => [
                'property' => 'groupe',
                'type' => 'int',
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by groupe',
                ],
            ],
            'sansScolarite' => [
                'property' => 'sansScolarite',
                'type' => 'bool',
                'required' => false,
                'openapi' => [
                    'description' => 'Filter students without any EtudiantScolarite',
                ],
            ],
            'actif' => [
                'property' => 'actif',
                'type' => 'bool',
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by active scolarite',
                ],
            ],
            'anneeSortie' => [
                'property' => 'anneeSortie',
                'type' => 'int',
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by annee_sortie (0 for active students)',
                ],
            ],
        ];
    }
}
