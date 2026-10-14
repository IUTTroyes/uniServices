<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

class EtudiantScolariteSemestreFilter implements FilterInterface
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

        if ('scolarite' === $property) {
            $queryBuilder
                ->join("$alias.scolarite", 'scolarite')
                ->andWhere('scolarite.id = :scolarite')
                ->setParameter("scolarite", $value);
        }
        if ('groupe' === $property) {
            // si c'est l'un des groupes lié via la proprité groupes de l'entité
            $queryBuilder
                ->join("$alias.groupes", 'groupe')
                ->andWhere('groupe.id = :groupe')
                ->setParameter("groupe", $value);
        }
        if ('semestre' === $property) {
            $queryBuilder
                ->join("$alias.semestre", 'semestre')
                ->andWhere('semestre.id = :semestre')
                ->setParameter("semestre", $value);
        }

        if ('etudiant' === $property) {
            $queryBuilder
                ->join("$alias.scolarite", 'scolarite2')
                ->join('scolarite2.etudiant', 'etudiant')
                ->andWhere('etudiant.id = :etudiant')
                ->setParameter("etudiant", $value);
        }
        if ('nom' === $property) {
            $queryBuilder
                ->join("$alias.scolarite", 'scolarite3')
                ->join('scolarite3.etudiant', 'etudiant2')
                ->andWhere('etudiant2.nom LIKE :nom')
                ->setParameter("nom", "$value%");
        }
        if ('prenom' === $property) {
            $queryBuilder
                ->join("$alias.scolarite", 'scolarite4')
                ->join('scolarite4.etudiant', 'etudiant3')
                ->andWhere('etudiant3.prenom LIKE :prenom')
                ->setParameter("prenom", "$value%");
        }
        if ('numEtudiant' === $property) {
            $queryBuilder
                ->join("$alias.scolarite", 'scolarite5')
                ->join('scolarite5.etudiant', 'etudiant4')
                ->andWhere('etudiant4.num_etudiant LIKE :numEtudiant')
                ->setParameter("numEtudiant", "$value%");
        }

        if ('anneeUniversitaire' === $property) {
            $queryBuilder
                ->join("$alias.scolarite", 'scolarite')
                ->andWhere('scolarite.anneeUniversitaire = :anneeUniversitaire')
                ->setParameter("anneeUniversitaire", $value);
        }
    }


}
