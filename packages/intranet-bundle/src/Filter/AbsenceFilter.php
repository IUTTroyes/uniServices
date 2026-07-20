<?php

namespace IntranetBundle\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

#[ApiFilter(AbsenceFilter::class)]
class AbsenceFilter extends AbstractFilter
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

        if ('semestre' === $property) {
            $scolariteSemestreAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $alias, 'scolariteSemestre');
            $semestreAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $scolariteSemestreAlias, 'semestre');
            $param = $queryNameGenerator->generateParameterName('semestre');

            $queryBuilder
                ->andWhere(sprintf('%s.id = :%s', $semestreAlias, $param))
                ->setParameter($param, $value);
        }

        if ('justifiee' === $property) {
            $param = $queryNameGenerator->generateParameterName('justifiee');

            $queryBuilder
                ->andWhere(sprintf('%s.justifiee = :%s', $alias, $param))
                ->setParameter($param, $value);
        }

        if ('event' === $property) {
            $eventAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $alias, 'event');
            $param = $queryNameGenerator->generateParameterName('event');

            $queryBuilder
                ->andWhere(sprintf('%s.id = :%s', $eventAlias, $param))
                ->setParameter($param, $value);
        }

        if ('personnel' === $property) {
            $personnelAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $alias, 'personnel');
            $param = $queryNameGenerator->generateParameterName('personnel');

            $queryBuilder
                ->andWhere(sprintf('%s.id = :%s', $personnelAlias, $param))
                ->setParameter($param, $value);
        }

        if ('scolariteSemestre' === $property) {
            $scolariteSemestreAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $alias, 'scolariteSemestre');
            $param = $queryNameGenerator->generateParameterName('scolariteSemestre');

            $queryBuilder
                ->andWhere(sprintf('%s.id = :%s', $scolariteSemestreAlias, $param))
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
            'justifiee' => [
                'property' => 'justifiee',
                'type' => Type::BUILTIN_TYPE_BOOL,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by justifiee',
                ],
            ],
            'event' => [
                'property' => 'event',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by event',
                ],
            ],
            'personnel' => [
                'property' => 'personnel',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by personnel',
                ],
            ],
            'scolariteSemestre' => [
                'property' => 'scolariteSemestre',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by scolariteSemestre',
                ],
            ],
        ];
    }
}
