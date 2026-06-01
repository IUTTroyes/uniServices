<?php

namespace HelpdeskBundle\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Operation;
use App\Entity\Structure\StructureAnnee;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

#[ApiFilter(TicketFilter::class)]
class TicketFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (null === $value) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        if ('latest' === $property) {
            $queryBuilder
                ->orderBy(sprintf('%s.created', $alias),'DESC')
                ->setMaxResults($value);
            ;
        }

        if ('auteur' === $property) {
            $queryBuilder
                ->andWhere(sprintf('%s.auteur = :%s', $alias,$property))
                ->setParameter($property, $value);
        }

        if ('statut' === $property) {
            $queryBuilder
                ->andWhere(sprintf('%s.statut = :%s', $alias,$property))
                ->setParameter($property, $value);
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'latest' => [
                'property' => 'latest',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by latests tickets',
                ],
            ],
            'auteur' => [
                'property' => 'auteur',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by auteur',
                ]
            ],
            'statut' => [
                'property' => 'statut',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by statut',
                ]
            ]
        ];

    }
}
