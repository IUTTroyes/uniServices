<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

class StagePeriodeFilter implements FilterInterface
{
    use BackwardCompatibleFilterDescriptionTrait;

    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (isset($context['parameter'])) {
            $parameter = $context['parameter'];
            $property = $parameter->getProperty() ?? $parameter->getKey();
            $this->filterProperty($property, $parameter->getValue(), $queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);

            return;
        }

        foreach ($context['filters'] ?? [] as $property => $value) {
            $this->filterProperty((string) $property, $value, $queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);
        }
    }

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
            $spAu = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $alias, 'anneeUniversitaire');
            $queryBuilder
                ->andWhere(sprintf('(%s.id = :stageAnneeUniv OR %s.annee = :stageAnneeUniv)', $spAu, $spAu))
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


}
