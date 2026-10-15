<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

class EtudiantScolariteFilter implements FilterInterface
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
        mixed $value,
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
                ->setParameter("nom", "$value%");
        }

        if ('prenom' === $property) {
            $queryBuilder
                ->join("$alias.etudiant", 'etudiant')
                ->andWhere('etudiant.prenom LIKE :prenom')
                ->setParameter("prenom", "$value%");
        }

        if ('mailUniv' === $property) {
            $queryBuilder
                ->join("$alias.etudiant", 'etudiant')
                ->andWhere('etudiant.mailUniv LIKE :mailUniv')
                ->setParameter("mailUniv", "$value%");
        }

        if ('annee' === $property) {
            $queryBuilder
                ->join("$alias.scolariteSemestre", 'scolariteSemestre')
                ->join('scolariteSemestre.semestre', 'semestre')
                ->join('semestre.annee', 'annee')
                ->andWhere('annee.id = :annee')
                ->setParameter("annee", $value);
        }



        if ('actif' === $property) {
            $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if (null !== $bool) {
                $queryBuilder
                    ->andWhere("$alias.actif = :scolActif")
                    ->setParameter('scolActif', $bool);
            }
        }

        if ('etudiant' === $property) {
            $queryBuilder
                ->join("$alias.etudiant", 'etudiant')
                ->andWhere('etudiant = :etudiant')
                ->setParameter("etudiant", $value);
        }
    }


}
