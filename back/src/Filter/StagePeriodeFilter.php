<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

#[ApiFilter(StagePeriodeFilter::class)]
class StagePeriodeFilter extends AbstractFilter
{
    protected function filterProperty(string $property, mixed $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        if ('departement' === $property) {
            $deptId = is_string($value) && str_contains($value, '/') ? (int) basename($value) : (int) $value;
            $spSem = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $alias, 'semestreProgramme');
            $spAnnee = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $spSem, 'annee');
            $spPn = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $spAnnee, 'pn');
            $spDiplome = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $spPn, 'diplome');
            $queryBuilder
                ->andWhere(sprintf('%s.departement = :stageDept', $spDiplome))
                ->setParameter('stageDept', $deptId);
        }

        if ('anneeUniversitaire' === $property) {
            $univId = is_string($value) && str_contains($value, '/') ? (int) basename($value) : (int) $value;
            $queryBuilder
                ->andWhere(sprintf('%s.anneeUniversitaire = :stageAnneeUniv', $alias))
                ->setParameter('stageAnneeUniv', $univId);
        }

        if ('actif' === $property) {
            $spAu = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $alias, 'anneeUniversitaire');
            $queryBuilder
                ->andWhere(sprintf('%s.actif = :stageAuActif', $spAu))
                ->setParameter('stageAuActif', filter_var($value, FILTER_VALIDATE_BOOLEAN));
        }

        if ('annee' === $property || 'ordre' === $property || 'ordreAnnee' === $property) {
            $spSem = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $alias, 'semestreProgramme');
            $spAnnee = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $spSem, 'annee');
            if (is_numeric($value)) {
                $queryBuilder
                    ->andWhere(sprintf('%s.ordre = :stageAnneeOrdre', $spAnnee))
                    ->setParameter('stageAnneeOrdre', (int) $value);
            }
        }

        if ('semestre' === $property) {
            $semId = is_string($value) && str_contains($value, '/') ? (int) basename($value) : (int) $value;
            $queryBuilder
                ->andWhere(sprintf('%s.semestreProgramme = :stageSemestre', $alias))
                ->setParameter('stageSemestre', $semId);
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
        $qb->leftJoin("$fromAlias.$association", $newAlias);
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
                    'description' => 'Filter periods by department ID',
                ],
            ],
            'anneeUniversitaire' => [
                'property' => 'anneeUniversitaire',
                'type' => 'int',
                'required' => false,
                'openapi' => [
                    'description' => 'Filter periods by academic year ID',
                ],
            ],
            'actif' => [
                'property' => 'actif',
                'type' => 'bool',
                'required' => false,
                'openapi' => [
                    'description' => 'Filter periods by active academic year',
                ],
            ],
            'annee' => [
                'property' => 'annee',
                'type' => 'int',
                'required' => false,
                'openapi' => [
                    'description' => 'Filter periods by year level/ordre (1, 2, or 3)',
                ],
            ],
            'semestre' => [
                'property' => 'semestre',
                'type' => 'int',
                'required' => false,
                'openapi' => [
                    'description' => 'Filter periods by semester ID',
                ],
            ],
        ];
    }
}
