<?php

namespace IntranetBundle\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

class JustificatifAbsenceFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (null === $value) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        if ('anneeUniversitaire' === $property) {
            $scolariteSemestreAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $alias, 'scolariteSemestre');
            $scolariteAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $scolariteSemestreAlias, 'scolarite');
            $anneeUniversitaireAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $scolariteAlias, 'anneeUniversitaire');
            $param = $queryNameGenerator->generateParameterName('anneeUniversitaire');

            $queryBuilder
                ->andWhere(sprintf('%s.id = :%s', $anneeUniversitaireAlias, $param))
                ->setParameter($param, $value);
        }

        if ('annee' === $property) {
            $scolariteSemestreAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $alias, 'scolariteSemestre');
            $semestreAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $scolariteSemestreAlias, 'semestre');
            $anneeAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $semestreAlias, 'annee');
            $param = $queryNameGenerator->generateParameterName('annee');

            $queryBuilder
                ->andWhere(sprintf('%s.id = :%s', $anneeAlias, $param))
                ->setParameter($param, $value);
        }

        if ('etat' === $property) {
            $param = $queryNameGenerator->generateParameterName('etat');

            $queryBuilder
                ->andWhere(sprintf('%s.etat = :%s', $alias, $param))
                ->setParameter($param, $value);
        }

        if ('debut' === $property) {
            $param = $queryNameGenerator->generateParameterName('debut');

            $queryBuilder
                ->andWhere(sprintf('%s.debut >= :%s', $alias, $param))
                ->setParameter($param, $value);
        }

        if ('fin' === $property) {
            $param = $queryNameGenerator->generateParameterName('fin');

            $queryBuilder
                ->andWhere(sprintf('%s.fin <= :%s', $alias, $param))
                ->setParameter($param, $value);
        }
    }

    private function getOrCreateJoin(QueryBuilder $qb, QueryNameGeneratorInterface $queryNameGenerator, string $fromAlias, string $association): string
    {
        foreach ($qb->getDQLPart('join')[$fromAlias] ?? [] as $join) {
            if ($join->getJoin() === sprintf('%s.%s', $fromAlias, $association)) {
                return $join->getAlias();
            }
        }

        $newAlias = $queryNameGenerator->generateJoinAlias($association);
        $qb->leftJoin(sprintf('%s.%s', $fromAlias, $association), $newAlias);

        return $newAlias;
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'annee' => [
                'property' => 'annee',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by annee',
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
            'etat' => [
                'property' => 'etat',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by etat',
                ],
            ],
            'debut' => [
                'property' => 'debut',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by start date',
                ],
            ],
            'fin' => [
                'property' => 'fin',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by end date',
                ],
            ],
        ];
    }
}
