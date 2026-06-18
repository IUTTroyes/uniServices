<?php

namespace HelpdeskBundle\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Operation;
use App\Entity\Structure\StructureAnnee;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

#[ApiFilter(ServiceFilter::class)]
class ServiceFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (null === $value) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

      if ('personnel' === $property) {
            $queryBuilder
                ->leftJoin(sprintf('%s.personnel', $alias),'p')
                ->andWhere('p.id = :personnel')
                ->setParameter("personnel", $value);
        }

    }

    public function getDescription(string $resourceClass): array
    {
        return [

        ];

    }
}
