<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

class DiplomeFilter implements FilterInterface
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

        if ('departement' === $property) {
            $parameter = $queryNameGenerator->generateParameterName('departement');
            $queryBuilder->andWhere(sprintf('%s.departement = :%s', $alias, $parameter))->setParameter($parameter, $value);
        } elseif ('anneeUniversitaire' === $property) {
            $pn = $queryNameGenerator->generateJoinAlias('pn');
            $parameter = $queryNameGenerator->generateParameterName('anneeUniversitaire');
            $queryBuilder->distinct()
                ->join(sprintf('%s.pns', $alias), $pn)
                ->andWhere(sprintf('%s.anneeUniversitaire = :%s', $pn, $parameter))
                ->setParameter($parameter, $value);
        }
    }
}
