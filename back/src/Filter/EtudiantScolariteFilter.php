<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

#[ApiFilter(EtudiantScolariteFilter::class)]
class EtudiantScolariteFilter extends AbstractFilter
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
            $queryBuilder
                ->join("$alias.departement", 'departement')
                ->andWhere('departement.id = :departement')
                ->setParameter("departement", $value);
        }

        if ('anneeUniversitaire' === $property) {
            $queryBuilder
                ->join("$alias.anneeUniversitaire", 'anneeUniversitaire')
                ->andWhere('anneeUniversitaire.id = :anneeUniversitaire')
                ->setParameter("anneeUniversitaire", $value);
        }

        if ('nom' === $property) {
            $queryBuilder
                ->join("$alias.etudiant", 'etudiant')
                ->andWhere('etudiant.nom LIKE :nom')
                ->setParameter("nom", "%$value%");
        }

        if ('prenom' === $property) {
            $queryBuilder
                ->join("$alias.etudiant", 'etudiant')
                ->andWhere('etudiant.prenom LIKE :prenom')
                ->setParameter("prenom", "%$value%");
        }

        if ('mailUniv' === $property) {
            $queryBuilder
                ->join("$alias.etudiant", 'etudiant')
                ->andWhere('etudiant.prenom LIKE :prenom')
                ->setParameter("prenom", "%$value%");
        }

        if ('annee' === $property) {
            $queryBuilder
                ->join("$alias.annee", 'annee')
                ->andWhere('annee.id = :annee')
                ->setParameter("annee", $value);
        }

        if ('etudiant' === $property) {
            $queryBuilder
                ->join("$alias.etudiant", 'etudiant')
                ->andWhere('etudiant = :etudiant')
                ->setParameter("etudiant", $value);
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
            'anneeUniversitaire' => [
                'property' => 'anneeUniversitaire',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by anneeUniversitaire',
                ],
            ],
            'etudiant' => [
                'property' => 'etudiant',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by etudiant',
                ],
            ],
        ];
    }
}
