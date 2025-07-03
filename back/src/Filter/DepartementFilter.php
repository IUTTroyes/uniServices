<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Operation;
use App\Entity\Structure\StructureDepartementPersonnel;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

#[ApiFilter(DepartementFilter::class)]
class DepartementFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (null === $value) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        if ('personnel' === $property) {
            $queryBuilder
                ->innerJoin(StructureDepartementPersonnel::class, 'sdp', 'WITH', sprintf('%s.id = sdp.departement', $alias))
                ->andWhere('sdp.personnel = :personnel')
                ->setParameter('personnel', $value)
                ->orderBy(sprintf('%s.libelle', $alias), 'ASC')
            ;
        } elseif ('actif' === $property) {
            $queryBuilder
                ->andWhere(sprintf('%s.actif = :actif', $alias))
                ->setParameter('actif', $value)
            ;
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'personnel' => [
                'property' => 'personnel',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by personnel',
                ],
            ],
            'actif' => [
                'property' => 'actif',
                'type' => Type::BUILTIN_TYPE_BOOL,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by active status',
                ],
            ],
        ];
    }
}
