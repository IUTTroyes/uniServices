<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

class AnneeFilter implements FilterInterface
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
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (null === $value) {
            return;
        }
        $alias = $queryBuilder->getRootAliases()[0];

        // Use the query name generator and reuse existing joins when possible
        if ('departement' === $property) {
            $pnAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $alias, 'pn');
            $dAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $pnAlias, 'diplome');
            $depAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $dAlias, 'departement');
            $param = $queryNameGenerator->generateParameterName('departement');

            $queryBuilder
                ->andWhere("$depAlias.id = :$param")
                ->setParameter($param, $value);
        }

        if ('pn' === $property) {
            $pnAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $alias, 'pn');
            $param = $queryNameGenerator->generateParameterName('pn');

            $queryBuilder
                ->andWhere("$pnAlias.id = :$param")
                ->setParameter($param, $value);
        }

        // anneeUniversitaire is not a direct property of Annee but of the related PN entity
        if ('anneeUniversitaire' === $property) {
            $pnAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $alias, 'pn');
            $anneeAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $pnAlias, 'anneeUniversitaire');
            $param = $queryNameGenerator->generateParameterName('anneeUniversitaire');

            // Accept either the anneeUniversitaire id or the numeric year value (annee)
            $queryBuilder
                ->andWhere("($anneeAlias.id = :$param OR $anneeAlias.annee = :$param)")
                ->setParameter($param, $value);
        }

        if ('diplome' === $property) {
            $pnAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $alias, 'pn');
            $dAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $pnAlias, 'diplome');
            $param = $queryNameGenerator->generateParameterName('diplome');

            $queryBuilder
                ->andWhere("$dAlias.id = :$param")
                ->setParameter($param, $value);
        }
        if ('actif' === $property) {
            // Normaliser la valeur en booléen (gère "true", "false", "1", "0", 1, 0, true, false)
            $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($bool === null) {
                // valeur non convertible -> ne pas appliquer le filtre
                return;
            }
            $queryBuilder
                ->andWhere("$alias.actif = :actif")
                ->setParameter("actif", $bool);
        }
    }


    /**
     * Reuse an existing join alias if present, otherwise create a new join and return its alias.
     * This prevents multiple joins to the same association with different aliases.
     */
    private function getOrCreateJoin(QueryBuilder $qb, QueryNameGeneratorInterface $queryNameGenerator, string $fromAlias, string $association): string
    {
        // Try to find an existing join for the association
        foreach ($qb->getDQLPart('join') as $alias => $joins) {
            foreach ($joins as $join) {
                if ($join->getJoin() === "$fromAlias.$association") {
                    return $join->getAlias();
                }
            }
        }

        // If not found, generate a new alias and add the join
        $newAlias = $queryNameGenerator->generateJoinAlias($association);
        $qb->join("$fromAlias.$association", $newAlias);
        return $newAlias;
    }
}
