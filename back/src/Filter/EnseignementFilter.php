<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\Operation;
use App\Service\Enseignement\AffectationsEnseignement;
use Doctrine\ORM\QueryBuilder;

class EnseignementFilter implements FilterInterface
{
    use BackwardCompatibleFilterDescriptionTrait;

    public function __construct(private readonly AffectationsEnseignement $affectations)
    {
    }

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

        $queryBuilder->distinct();

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
            $source = $this->affectations->source();
            if ($source === null) {
                $queryBuilder->andWhere('1 = 0');
            } else {
                $personnel = $queryNameGenerator->generateParameterName('personnel');
                $queryBuilder->setParameter($personnel, $value);
                $anneeParametre = null;
                if ($context['filters']['anneeUniversitaire'] ?? null) {
                    $anneeParametre = $queryNameGenerator->generateParameterName('anneeUniversitaireId');
                    $queryBuilder->setParameter($anneeParametre, $context['filters']['anneeUniversitaire']);
                }
                $queryBuilder->andWhere(sprintf('%s.id IN (%s)', $alias, $source->dqlEnseignements(
                    $queryNameGenerator->generateJoinAlias('affectation'), $personnel, $anneeParametre,
                )));
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
                $queryBuilder
                    ->andWhere("$anneeUniversitaireAlias.id = :anneeUniversitaireId")
                    ->setParameter("anneeUniversitaireId", $anneeUniversitaireId);
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
