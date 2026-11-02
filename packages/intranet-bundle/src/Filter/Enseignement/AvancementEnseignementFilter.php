<?php

namespace IntranetBundle\Filter\Enseignement;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

class AvancementEnseignementFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if ($value === null || !in_array($property, ['enseignement', 'anneeUniversitaire', 'groupe', 'avant'], true)) return;
        $alias = $queryBuilder->getRootAliases()[0];
        $parameter = $queryNameGenerator->generateParameterName($property);
        if ($property === 'avant') {
            $queryBuilder->andWhere(sprintf('%s.id < :%s', $alias, $parameter));
        } else {
            $queryBuilder->andWhere(sprintf('%s.%s = :%s', $alias, $property, $parameter));
        }
        $queryBuilder->setParameter($parameter, $value);
    }

    public function getDescription(string $resourceClass): array
    {
        $description = [];
        foreach (['enseignement', 'anneeUniversitaire', 'groupe', 'avant'] as $property) {
            $description[$property] = ['property' => $property, 'type' => 'int', 'required' => false];
        }
        return $description;
    }
}
