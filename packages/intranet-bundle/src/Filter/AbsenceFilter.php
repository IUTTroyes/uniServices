<?php

namespace IntranetBundle\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use IntranetBundle\Enum\EtatJustificatifEnum;

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

        if ('annee' === $property) {
            $scolariteSemestreAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $alias, 'scolariteSemestre');
            $semestreAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $scolariteSemestreAlias, 'semestre');
            $anneeAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $semestreAlias, 'annee');
            $param = $queryNameGenerator->generateParameterName('annee');

            $queryBuilder
                ->andWhere(sprintf('%s.id = :%s', $anneeAlias, $param))
                ->setParameter($param, $value);
        }

        if ('justifiee' === $property) {
            $param = $queryNameGenerator->generateParameterName('justifiee');
            $justificatifAlias = $this->getOrCreateJoin($queryBuilder, $queryNameGenerator, $alias, 'absenceJustificatif');

            if (filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)) {
                $queryBuilder
                    ->andWhere(sprintf('%s.etat = :%s', $justificatifAlias, $param))
                    ->setParameter($param, EtatJustificatifEnum::VALIDE);
            } else {
                $queryBuilder
                    ->andWhere(sprintf('(%s.id IS NULL OR %s.etat != :%s)', $justificatifAlias, $justificatifAlias, $param))
                    ->setParameter($param, EtatJustificatifEnum::VALIDE);
            }
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
            'annee' => [
                'property' => 'annee',
                'type' => 'int',
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by annee',
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
            'justifiee' => [
                'property' => 'justifiee',
                'type' => 'bool',
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by justifiee',
                ],
            ],
            'event' => [
                'property' => 'event',
                'type' => 'int',
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by event',
                ],
            ],
            'personnel' => [
                'property' => 'personnel',
                'type' => 'int',
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by personnel',
                ],
            ],
            'scolariteSemestre' => [
                'property' => 'scolariteSemestre',
                'type' => 'int',
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by scolariteSemestre',
                ],
            ],
        ];
    }
}
