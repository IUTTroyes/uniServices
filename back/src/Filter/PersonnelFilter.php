<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

#[ApiFilter(PersonnelFilter::class)]
class PersonnelFilter extends AbstractFilter
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

        if ('departement' === $property) {
            $departementPersonnelAlias = $queryNameGenerator->generateJoinAlias('departementPersonnels');
            $departementAlias = $queryNameGenerator->generateJoinAlias('departement');

            $queryBuilder
                ->leftJoin(sprintf('%s.departementPersonnels', $alias), $departementPersonnelAlias)
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

        if ('statut' === $property) {
            $queryBuilder
                ->andWhere(sprintf('%s.statut LIKE :statut', $alias))
                ->setParameter('statut', "$value%");
        }

    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'departement' => [
                'property' => 'departement',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by departement',
                ],
            ],
            'enseignement' => [
                'property' => 'enseignement',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by enseignement',
                ],
            ],
            'enseignant' => [
                'property' => 'enseignant',
                'type' => Type::BUILTIN_TYPE_BOOL,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by enseignant',
                ],]
        ];
    }
}
