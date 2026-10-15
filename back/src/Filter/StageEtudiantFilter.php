<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

class StageEtudiantFilter implements FilterInterface
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

        if ('tuteurUniversitaire' === $property) {
            $tutId = $this->resolveEntityId($value);
            if ($tutId !== null) {
                $tutAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $alias, 'tuteurUniversitaire');
                $queryBuilder
                    ->andWhere(sprintf('%s.id = :stageTuteurUnivId', $tutAlias))
                    ->setParameter('stageTuteurUnivId', $tutId);
            }
        }

        if ('stagePeriode' === $property) {
            $periodeId = $this->resolveEntityId($value);
            if ($periodeId !== null) {
                $queryBuilder
                    ->andWhere(sprintf('%s.stagePeriode = :stagePeriodeId', $alias))
                    ->setParameter('stagePeriodeId', $periodeId);
            }
        }

        if ('etudiant' === $property) {
            $etudiantId = $this->resolveEntityId($value);
            if ($etudiantId !== null) {
                $queryBuilder
                    ->andWhere(sprintf('%s.etudiant = :stageEtudiantId', $alias))
                    ->setParameter('stageEtudiantId', $etudiantId);
            }
        }

        if ('anneeUniversitaire' === $property) {
            $univId = $this->resolveEntityId($value);
            if ($univId !== null) {
                $spAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $alias, 'stagePeriode');
                $auAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $spAlias, 'anneeUniversitaire');
                $queryBuilder
                    ->andWhere(sprintf('(%s.id = :stageEtudiantAnneeUniv OR %s.annee = :stageEtudiantAnneeUniv)', $auAlias, $auAlias))
                    ->setParameter('stageEtudiantAnneeUniv', $univId);
            }
        }

        if ('departement' === $property) {
            $deptId = $this->resolveEntityId($value);
            if ($deptId !== null) {
                $spAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $alias, 'stagePeriode');
                $spSem = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $spAlias, 'semestreProgramme');
                $spAnnee = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $spSem, 'annee');
                $spPn = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $spAnnee, 'pn');
                $spDiplome = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $spPn, 'diplome');
                $queryBuilder
                    ->andWhere(sprintf('%s.departement = :stageEtudiantDept', $spDiplome))
                    ->setParameter('stageEtudiantDept', $deptId);
            }
        }
    }

    private function resolveEntityId(mixed $value): ?int
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (is_object($value) && method_exists($value, 'getId')) {
            $id = $value->getId();
            return is_numeric($id) ? (int) $id : null;
        }

        if (is_string($value)) {
            if (str_contains($value, '/')) {
                $base = basename($value);
                return is_numeric($base) ? (int) $base : null;
            }
            return is_numeric($value) ? (int) $value : null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
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
