<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

#[ApiFilter(CompetenceFilter::class)]
class CompetenceFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (null === $value) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        // si l'ue passée en paramètre fait partie de la collection des ues de la compétence

        if ('ue' === $property) {
            $ueAlias = $queryNameGenerator->generateJoinAlias('ue');
            $param = $queryNameGenerator->generateParameterName('ue');

            $queryBuilder
                ->innerJoin(sprintf('%s.ues', $alias), $ueAlias)
                ->andWhere(sprintf('%s.id = :%s', $ueAlias, $param))
                ->setParameter($param, $value);

            return;
        }

        if ('referentiel' === $property) {
            $refAlias = $queryNameGenerator->generateJoinAlias('referentiel');
            $param = $queryNameGenerator->generateParameterName('referentiel');

            $queryBuilder
                ->innerJoin(sprintf('%s.referentiel', $alias), $refAlias)
                ->andWhere(sprintf('%s.id = :%s', $refAlias, $param))
                ->setParameter($param, $value)
            ;
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'ue' => [
                'property' => 'ue',
                'type' => 'int',
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by ue',
                ],
            ],
            'referentiel' => [
                'property' => 'referentiel',
                'type' => 'int',
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by referentiel',
                ],
            ],
        ];
    }
}
