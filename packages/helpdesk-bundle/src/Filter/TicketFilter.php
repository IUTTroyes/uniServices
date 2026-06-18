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
        if ('hasRecentMessage' === $property && $value) {
            $dateLimite = new \DateTimeImmutable('-7 days');

            $queryBuilder
                ->leftJoin(sprintf('%s.helpdeskMessages', $alias), 'm')
                ->andWhere('m.created >= :dateLimite')
                ->setParameter('dateLimite', $dateLimite);
        }
        if ('service' === $property) {
            $queryBuilder
                ->leftJoin(sprintf('%s.helpdeskCategorie', $alias), 'c')
                ->leftJoin(sprintf('c.service'), 's')
                ->andWhere(sprintf('%s.id = :%s', 's',$property))
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
            ],
            'hasRecentMessage' => [
                'property' => 'hasRecentMessage',
                'type' => Type::BUILTIN_TYPE_BOOL,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by new message',
                ]
            ]
        ];

    }
}
