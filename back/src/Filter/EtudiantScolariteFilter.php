<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

class EtudiantScolariteFilter implements FilterInterface
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

    protected function filterProperty(
        string $property,
        mixed $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if (null === $value || '' === $value) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        if ('search' === $property) {
            $etudiantAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $alias, 'etudiant');
            $terms = array_values(array_filter(explode(' ', trim((string) $value))));
            if (!empty($terms)) {
                $termIndex = 0;
                foreach ($terms as $term) {
                    $searchParam = $queryNameGenerator->generateParameterName('search_term_' . $termIndex);
                    $queryBuilder->andWhere(sprintf(
                        '(LOWER(%1$s.nom) LIKE LOWER(:%2$s) ' .
                        'OR LOWER(%1$s.prenom) LIKE LOWER(:%2$s) ' .
                        'OR LOWER(%1$s.mailUniv) LIKE LOWER(:%2$s) ' .
                        'OR %1$s.num_etudiant LIKE :%2$s ' .
                        'OR LOWER(%1$s.username) LIKE LOWER(:%2$s))',
                        $etudiantAlias,
                        $searchParam
                    ))->setParameter($searchParam, '%' . $term . '%');
                    $termIndex++;
                }
            }
        }

        if ('departement' === $property) {
            $deptAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $alias, 'departement');
            $deptParam = $queryNameGenerator->generateParameterName('departement');
            $queryBuilder
                ->andWhere(sprintf('%s.id = :%s', $deptAlias, $deptParam))
                ->setParameter($deptParam, $value);
        }

        if ('anneeUniversitaire' === $property) {
            $auAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $alias, 'anneeUniversitaire');
            $auParam = $queryNameGenerator->generateParameterName('anneeUniversitaire');
            $queryBuilder
                ->andWhere(sprintf('%s.id = :%s', $auAlias, $auParam))
                ->setParameter($auParam, $value);
        }

        if ('nom' === $property) {
            $etudiantAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $alias, 'etudiant');
            $nomParam = $queryNameGenerator->generateParameterName('nom');
            $queryBuilder
                ->andWhere(sprintf('LOWER(%s.nom) LIKE LOWER(:%s)', $etudiantAlias, $nomParam))
                ->setParameter($nomParam, trim((string) $value) . '%');
        }

        if ('prenom' === $property) {
            $etudiantAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $alias, 'etudiant');
            $prenomParam = $queryNameGenerator->generateParameterName('prenom');
            $queryBuilder
                ->andWhere(sprintf('LOWER(%s.prenom) LIKE LOWER(:%s)', $etudiantAlias, $prenomParam))
                ->setParameter($prenomParam, trim((string) $value) . '%');
        }

        if ('mailUniv' === $property) {
            $etudiantAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $alias, 'etudiant');
            $mailParam = $queryNameGenerator->generateParameterName('mailUniv');
            $queryBuilder
                ->andWhere(sprintf('LOWER(%s.mailUniv) LIKE LOWER(:%s)', $etudiantAlias, $mailParam))
                ->setParameter($mailParam, trim((string) $value) . '%');
        }

        if ('annee' === $property) {
            $ssAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $alias, 'scolariteSemestre');
            $semestreAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $ssAlias, 'semestre');
            $anneeAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $semestreAlias, 'annee');
            $anneeParam = $queryNameGenerator->generateParameterName('annee');
            $queryBuilder
                ->andWhere(sprintf('%s.id = :%s', $anneeAlias, $anneeParam))
                ->setParameter($anneeParam, $value);
        }

        if ('actif' === $property) {
            $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if (null !== $bool) {
                $actifParam = $queryNameGenerator->generateParameterName('scolActif');
                $queryBuilder
                    ->andWhere(sprintf('%s.actif = :%s', $alias, $actifParam))
                    ->setParameter($actifParam, $bool);
            }
        }

        if ('etudiant' === $property) {
            $etudiantAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $alias, 'etudiant');
            $etudiantParam = $queryNameGenerator->generateParameterName('etudiant');
            $queryBuilder
                ->andWhere(sprintf('%s.id = :%s', $etudiantAlias, $etudiantParam))
                ->setParameter($etudiantParam, $value);
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


}
