<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

#[ApiFilter(EvaluationFilter::class)]
class EvaluationFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (null === $value) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        if ('enseignement' === $property) {
            $queryBuilder
                ->andWhere(sprintf('%s.enseignement = :enseignement', $alias))
                ->setParameter('enseignement', $value);
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'enseignement' => [
                'property' => 'enseignement',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'description' => 'Filter evaluations by enseignement ID.',
            ],
        ];
    }
}
