<?php

namespace IntranetBundle\Filter\PlanCours;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

class PlanCoursFilter extends AbstractFilter
{
    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if (null === $value) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        if ('anneeUniversitaire' === $property) {
            $param = $queryNameGenerator->generateParameterName('anneeUniversitaire');
            $queryBuilder
                ->andWhere(sprintf('%s.anneeUniversitaire = :%s', $alias, $param))
                ->setParameter($param, (int) $value);
        } elseif ('enseignement' === $property) {
            $param = $queryNameGenerator->generateParameterName('enseignement');
            $queryBuilder
                ->andWhere(sprintf('%s.enseignement = :%s', $alias, $param))
                ->setParameter($param, (int) $value);
        } elseif ('responsable' === $property) {
            $param = $queryNameGenerator->generateParameterName('responsable');
            $queryBuilder
                ->andWhere(sprintf('%s.responsable = :%s', $alias, $param))
                ->setParameter($param, (int) $value);
        } elseif ('etatPlanCours' === $property) {
            $param = $queryNameGenerator->generateParameterName('etatPlanCours');
            $queryBuilder
                ->andWhere(sprintf('%s.etatPlanCours = :%s', $alias, $param))
                ->setParameter($param, $value);
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'anneeUniversitaire' => [
                'property' => 'anneeUniversitaire',
                'type' => 'int',
                'required' => false,
                'openapi' => ['description' => 'Filtre par année universitaire'],
            ],
            'enseignement' => [
                'property' => 'enseignement',
                'type' => 'int',
                'required' => false,
                'openapi' => ['description' => 'Filtre par enseignement'],
            ],
            'responsable' => [
                'property' => 'responsable',
                'type' => 'int',
                'required' => false,
                'openapi' => ['description' => 'Filtre par responsable'],
            ],
            'etatPlanCours' => [
                'property' => 'etatPlanCours',
                'type' => 'string',
                'required' => false,
                'openapi' => ['description' => 'Filtre par état du plan de cours (NONE, EN_COURS, COMPLET)'],
            ],
        ];
    }
}
