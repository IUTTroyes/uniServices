<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

class PersonnelFilter implements FilterInterface
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

        if ('departement' === $property) {
            $departementPersonnelAlias = $queryNameGenerator->generateJoinAlias('departementPersonnels');
            $departementAlias = $queryNameGenerator->generateJoinAlias('departement');

            $queryBuilder
                ->leftJoin(sprintf('%s.departementsPersonnel', $alias), $departementPersonnelAlias)
                ->leftJoin(sprintf('%s.departement', $departementPersonnelAlias), $departementAlias)
                ->andWhere(sprintf('%s.id = :departement', $departementAlias))
                ->setParameter('departement', $value);
        }
        if ('enseignement' === $property) {
            $enseignementPersonnelAlias = $queryNameGenerator->generateJoinAlias('enseignementPersonnels');
            $enseignementAlias = $queryNameGenerator->generateJoinAlias('enseignement');

            $queryBuilder
                ->leftJoin(sprintf('%s.previsionnels', $alias), $enseignementPersonnelAlias)
                ->leftJoin(sprintf('%s.enseignement', $enseignementPersonnelAlias), $enseignementAlias)
                ->andWhere(sprintf('%s.id = :enseignement', $enseignementAlias))
                ->setParameter('enseignement', $value);
        }
        if (('enseignant' === $property) && $value) {
            // si true : récupérer les personnels dont statut != "BIATSS"
            $queryBuilder
                ->andWhere(sprintf('%s.statut != :statut', $alias))
                ->setParameter('statut', 'BIATSS');
        }

        if ('nom' === $property) {
            $queryBuilder
                ->andWhere(sprintf('%s.nom LIKE :nom', $alias))
                ->setParameter('nom', "$value%");
        }

        if ('prenom' === $property) {
            $queryBuilder
                ->andWhere(sprintf('%s.prenom LIKE :prenom', $alias))
                ->setParameter('prenom', "$value%");
        }

        if ('mailUniv' === $property) {
            $queryBuilder
                ->andWhere(sprintf('%s.mailUniv LIKE :mailUniv', $alias))
                ->setParameter('mailUniv', "$value%");
        }

        if ('numeroHarpege' === $property) {
            $queryBuilder
                ->andWhere(sprintf('%s.numeroHarpege LIKE :numeroHarpege', $alias))
                ->setParameter('numeroHarpege', "$value%");
        }

        if ('username' === $property) {
            $queryBuilder
                ->andWhere(sprintf('%s.username LIKE :username', $alias))
                ->setParameter('username', "$value%");
        }

        if ('statut' === $property) {
            $queryBuilder
                ->andWhere(sprintf('%s.statut LIKE :statut', $alias))
                ->setParameter('statut', "$value%");
        }

    }


}
