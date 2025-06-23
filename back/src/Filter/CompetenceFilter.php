<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Operation;
use App\Entity\Structure\StructureAnnee;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

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
            $queryBuilder
                ->innerJoin('o.ues', 'ue')
                ->andWhere('ue.id = :ue')
                ->setParameter('ue', $value)
            ;
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'ue' => [
                'property' => 'ue',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by ue',
                ],
            ],
        ];
    }
}
