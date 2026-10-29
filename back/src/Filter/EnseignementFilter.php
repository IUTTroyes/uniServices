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
            $enseignementUeAlias = $queryNameGenerator->generateJoinAlias('enseignementUe');
            $ueAlias = $queryNameGenerator->generateJoinAlias('ue');

            $queryBuilder
                ->join("$alias.enseignementUes", $enseignementUeAlias)
                ->join("$enseignementUeAlias.ue", $ueAlias)
                ->andWhere("$ueAlias.semestre = :semestre")
                ->setParameter("semestre", $value);
        }

        if ('personnel' === $property) {
            $previsionnelAlias = $queryNameGenerator->generateJoinAlias('previsionnel');
            $personnelAlias = $queryNameGenerator->generateJoinAlias('personnel');
            $anneeUniversitaireAlias = $queryNameGenerator->generateJoinAlias('anneeUniversitaire');

            $queryBuilder
                // si il y a des previsionnels avec des personnels
                ->join("$alias.previsionnels", $previsionnelAlias)
                ->join("$previsionnelAlias.personnel", $personnelAlias)
                ->andWhere("$personnelAlias.id = :personnel")
                ->setParameter("personnel", $value);
                if ($context['filters']['anneeUniversitaire'] ?? null) {
                    $queryBuilder
                        ->join("$previsionnelAlias.anneeUniversitaire", $anneeUniversitaireAlias)
                        ->andWhere("$anneeUniversitaireAlias.id = :anneeUniversitaireId")
                        ->setParameter("anneeUniversitaireId", $context['filters']['anneeUniversitaire']);
                } else {
                    $queryBuilder
                        ->join("$previsionnelAlias.anneeUniversitaire", $anneeUniversitaireAlias)
                        ->andWhere("$anneeUniversitaireAlias.actif = true");
                }
        }

        if ('annee' === $property) {
            $enseignementUeAlias = $queryNameGenerator->generateJoinAlias('enseignementUe');
            $ueAlias = $queryNameGenerator->generateJoinAlias('ue');
            $semestreAlias = $queryNameGenerator->generateJoinAlias('semestre');

            $queryBuilder
                ->join("$alias.enseignementUes", $enseignementUeAlias)
                ->join("$enseignementUeAlias.ue", $ueAlias)
                ->join("$ueAlias.semestre", $semestreAlias)
                ->andWhere("$semestreAlias.annee = :annee")
                ->setParameter("annee", $value);
        }

        if ('departement' === $property) {
            $enseignementUeAlias = $queryNameGenerator->generateJoinAlias('enseignementUe');
            $ueAlias = $queryNameGenerator->generateJoinAlias('ue');
            $semestreAlias = $queryNameGenerator->generateJoinAlias('semestre');
            $anneeAlias = $queryNameGenerator->generateJoinAlias('annee');
            $pnAlias = $queryNameGenerator->generateJoinAlias('pn');
            $anneeUniversitaireAlias = $queryNameGenerator->generateJoinAlias('anneeUniversitaire');
            $diplomeAlias = $queryNameGenerator->generateJoinAlias('diplome');
            $departementAlias = $queryNameGenerator->generateJoinAlias('departement');

            $queryBuilder
                ->join("$alias.enseignementUes", $enseignementUeAlias)
                ->join("$enseignementUeAlias.ue", $ueAlias)
                ->join("$ueAlias.semestre", $semestreAlias)
                ->join("$semestreAlias.annee" , $anneeAlias)
                ->join("$anneeAlias.pn", $pnAlias)
                ->join("$pnAlias.anneeUniversitaire", $anneeUniversitaireAlias)
                ->join("$pnAlias.diplome", $diplomeAlias)
                ->join("$diplomeAlias.departement", $departementAlias)
                ->andWhere("$departementAlias.id = :departement")
                ->setParameter("departement", $value);

            $anneeUniversitaireId = $context['filters']['anneeUniversitaire'] ?? null;
            if ($anneeUniversitaireId) {
                $diplomeAnneeUnivAlias = $queryNameGenerator->generateJoinAlias('diplomeAnneeUniv');

                $queryBuilder
                    ->join("$diplomeAlias.anneesUniversitaires", $diplomeAnneeUnivAlias)
                    ->andWhere("$diplomeAnneeUnivAlias.id = :anneeUniversitaireId")
                    ->andWhere("$anneeUniversitaireAlias.id = :anneeUniversitaireId")
                    ->setParameter("anneeUniversitaireId", $anneeUniversitaireId);
            } else {
                $queryBuilder
                    ->andWhere("$anneeUniversitaireAlias.actif = true");
            }
        }


        if ('ue' === $property) {
            $enseignementUeAlias = $queryNameGenerator->generateJoinAlias('enseignementUe');
            $ueAlias = $queryNameGenerator->generateJoinAlias('ue');

            $queryBuilder
                ->join("$alias.enseignementUes", $enseignementUeAlias)
                ->join("$enseignementUeAlias.ue", $ueAlias)
                ->andWhere("$ueAlias.id = :ue")
                ->setParameter("ue", $value);
        }
    }


}
