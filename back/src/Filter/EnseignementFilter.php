<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

class EnseignementFilter implements FilterInterface
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

        if ('semestre' === $property) {
            $queryBuilder
                ->join("$alias.enseignementUes", "enseignementUe")
                ->join("enseignementUe.ue", "ue")
                ->andWhere("ue.semestre = :semestre")
                ->setParameter("semestre", $value);
        }

        if ('personnel' === $property) {
            $queryBuilder
                // si il y a des previsionnels avec des personnels
                ->join("$alias.previsionnels", "previsionnel")
                ->join("previsionnel.personnel", "personnel")
                ->andWhere("personnel.id = :personnel")
                ->setParameter("personnel", $value);
                if ($context['filters']['anneeUniversitaire'] ?? null) {
                    $queryBuilder
                        ->join("previsionnel.anneeUniversitaire", "anneeUniversitaire")
                        ->andWhere("anneeUniversitaire.id = :anneeUniversitaireId")
                        ->setParameter("anneeUniversitaireId", $context['filters']['anneeUniversitaire']);
                } else {
                    $queryBuilder
                        ->join("previsionnel.anneeUniversitaire", "anneeUniversitaire")
                        ->andWhere("anneeUniversitaire.actif = true");
                }
        }

        if ('annee' === $property) {
            $queryBuilder
                ->join("$alias.enseignementUes", "enseignementUe2")
                ->join("enseignementUe2.ue", "ue2")
                ->join("ue2.semestre", "semestre2")
                ->andWhere("semestre2.annee = :annee")
                ->setParameter("annee", $value);
        }

        if ('departement' === $property) {
            $queryBuilder
                ->join("$alias.enseignementUes", "enseignementUe")
                ->join("enseignementUe.ue", "ue")
                ->join("ue.semestre", "semestre")
                ->join("semestre.annee" , "annee")
                ->join("annee.pn", "pn")
                ->join("pn.anneeUniversitaire", "anneeUniversitaire")
                ->join("pn.diplome", "diplome")
                ->join("diplome.departement", "departement")
                ->andWhere("departement.id = :departement")
                ->setParameter("departement", $value);

            $anneeUniversitaireId = $context['filters']['anneeUniversitaire'] ?? null;
            if ($anneeUniversitaireId) {
                $queryBuilder
                    ->join("diplome.anneesUniversitaires", "diplomeAnneeUniv")
                    ->andWhere("diplomeAnneeUniv.id = :anneeUniversitaireId")
                    ->andWhere("anneeUniversitaire.id = :anneeUniversitaireId")
                    ->setParameter("anneeUniversitaireId", $anneeUniversitaireId);
            } else {
                $queryBuilder
                    ->andWhere("anneeUniversitaire.actif = true");
            }
        }


        if ('ue' === $property) {
            $queryBuilder
                ->join("$alias.enseignementUes", "enseignementUe")
                ->join("enseignementUe.ue", "ue")
                ->andWhere("ue.id = :ue")
                ->setParameter("ue", $value);
        }
    }


}
