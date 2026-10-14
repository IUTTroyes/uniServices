<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\Operation;
use App\Entity\Structure\StructureAnnee;
use Doctrine\ORM\QueryBuilder;

class SemestreFilter implements FilterInterface
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

        if ('diplome' === $property) {
            $queryBuilder
                ->innerJoin(StructureAnnee::class, 'sa', 'WITH', sprintf('%s.annee = sa.id', $alias))
                ->innerJoin('sa.pn', 'pn')
                ->innerJoin('pn.anneeUniversitaire', 'au')
                ->innerJoin('pn.diplome', 'd')
                ->andWhere('au.actif = true')
                ->andWhere('d.id = :diplome')
                ->setParameter('diplome', $value)
                ->orderBy(sprintf('%s.ordreLmd', $alias), 'ASC')
                ->addOrderBy(sprintf('%s.libelle', $alias), 'ASC')
            ;
        } else if ('departement' === $property) {
            $queryBuilder
                ->innerJoin(StructureAnnee::class, 'sa', 'WITH', sprintf('%s.annee = sa.id', $alias))
                ->innerJoin('sa.pn', 'pn')
                ->innerJoin('pn.anneeUniversitaire', 'au')
                ->innerJoin('pn.diplome', 'd')
                ->andWhere('au.actif = true')
                ->andWhere('d.departement = :departement')
                ->setParameter('departement', $value)
                ->orderBy(sprintf('%s.ordreLmd', $alias), 'ASC')
                ->addOrderBy(sprintf('%s.libelle', $alias), 'ASC')
            ;
        } elseif ('annee' === $property) {
            $queryBuilder
                ->andWhere(sprintf('%s.annee = :annee', $alias))
                ->setParameter('annee', $value)
            ;
        } elseif ('actif' === $property) {
            $queryBuilder
                ->andWhere(sprintf('%s.actif = :actif', $alias))
                ->setParameter('actif', filter_var($value, FILTER_VALIDATE_BOOLEAN))
            ;
        }
    }
}
