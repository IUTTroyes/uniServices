<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

class PnFilter implements FilterInterface
{
    use BackwardCompatibleFilterDescriptionTrait;

    private const FILTERS = [
        'personnel' => 'personnel',
        'anneeUniversitaire' => 'anneeUniversitaire',
        'departement' => 'departement',
        'enseignement' => 'enseignement',
        'diplome' => 'diplome',
    ];

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
        if (!in_array($property, self::FILTERS) || null === $value) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        if ('diplome' === $property) {
            $queryBuilder
                ->join(sprintf('%s.diplome', $alias), 'diplome')
                ->andWhere('diplome.id = :diplome')
                ->setParameter('diplome', $value)
            ;
        }

        if ('anneeUniversitaire' === $property) {
            $queryBuilder
                ->join("$alias.anneeUniversitaire", "anneeUniversitaire")
                ->andWhere("anneeUniversitaire.id = :anneeUniversitaire")
                ->setParameter("anneeUniversitaire", $value)
            ;
        }
    }
}
