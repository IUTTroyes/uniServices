<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Operation;
use App\Entity\Structure\StructureAnnee;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

#[ApiFilter(SemestreFilter::class)]
class SemestreFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (null === $value) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        if ('diplome' === $property) {
            $queryBuilder
                ->innerJoin(StructureAnnee::class, 'sa', 'WITH', sprintf('%s.annee = sa.id', $alias))
                ->andWhere('sa.diplome = :diplome')
                ->setParameter('diplome', $value)
                ->orderBy(sprintf('%s.ordreLmd', $alias), 'ASC')
                ->addOrderBy(sprintf('%s.libelle', $alias), 'ASC')
            ;
        } else if ('departement' === $property) {
            $queryBuilder
                ->innerJoin(StructureAnnee::class, 'sa', 'WITH', sprintf('%s.annee = sa.id', $alias))
                ->innerJoin('sa.pn', 'pn')
                ->innerJoin('pn.diplome', 'd')
                ->andWhere('d.departement = :departement')
                ->setParameter('departement', $value)
                ->orderBy(sprintf('%s.ordreLmd', $alias), 'ASC')
                ->addOrderBy(sprintf('%s.libelle', $alias), 'ASC')
            ;
        } elseif ('annee' === $property) {
            $queryBuilder
                ->andWhere(sprintf('%s.annee = :annee', $alias))
                ->setParameter('annee', $value)
            ;
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'departement' => [
                'property' => 'departement',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by département',
                ],
            ],
            'diplome' => [
                'property' => 'diplome',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by diplôme',
                ],
            ],
            'annee' => [
                'property' => 'annee',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by year',
                ],
            ],
        ];
    }
}
