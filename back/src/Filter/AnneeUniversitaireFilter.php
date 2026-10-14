<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

class AnneeUniversitaireFilter implements FilterInterface
{
    use BackwardCompatibleFilterDescriptionTrait;

    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (isset($context['parameter'])) {
            $parameter = $context['parameter'];
            $property = $parameter->getProperty() ?? $parameter->getKey();
            $this->filterProperty($property, $parameter->getValue(), $queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);

            return;
        }

        foreach ($context['filters'] ?? [] as $property => $value) {
            $this->filterProperty((string) $property, $value, $queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);
        }
    }

    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (null === $value) {
            return;
        }
        $alias = $queryBuilder->getRootAliases()[0];

        // Use the query name generator and reuse existing joins when possible
        if ('since' === $property) {
            // récupérer celle dont annee universitaire est supérieur ou égal à la valeur de since
            $queryBuilder
                ->andWhere(sprintf('%s.annee >= :since', $alias))
                ->setParameter('since', $value);
        }
        if ('until' === $property) {
            // récupérer celle dont annee universitaire est inférieur ou égal à la valeur de until
            $queryBuilder
                ->andWhere(sprintf('%s.annee <= :until', $alias))
                ->setParameter('until', $value);
        }
    }
}
