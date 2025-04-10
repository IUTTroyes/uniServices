<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Scolarite\ScolEnseignementUe;
use App\Entity\Structure\StructureAnnee;
use App\Entity\Structure\StructureUe;
use Doctrine\ORM\QueryBuilder;
use ApiPlatform\Metadata\ApiFilter;
use Symfony\Component\PropertyInfo\Type;

#[ApiFilter(EtudiantFilter::class)]
class EtudiantFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (null === $value) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        if ('departement' === $property) {
            $queryBuilder
                ->join("$alias.etudiantScolarites", "es1")
                ->join("es1.departement", "d")
                ->andWhere("d.id = :departement")
                ->setParameter("departement", $value);
        }

        if ('anneeUniversitaire' === $property) {
            $queryBuilder
                ->join("$alias.etudiantScolarites", "es2")
                ->join("es2.structureAnneeUniversitaire", "sau")
                ->andWhere("sau.id = :anneeUniversitaire")
                ->setParameter("anneeUniversitaire", $value);
        }

        if ('semestre' === $property) {
            $queryBuilder
                ->join("$alias.etudiantScolarites", "es3")
                ->join("es3.scolarite_semestre", "ss")
                ->join("ss.structure_semestre", "s")
                ->join("es3.structureAnneeUniversitaire", "sau2") // Ajout du join
                ->andWhere("s.id = :semestre")
                ->andWhere("sau2.actif = true") // Utilisation correcte du champ actif
                ->setParameter("semestre", $value);
        }

//        if ('annee' === $property) {
//            $queryBuilder
//                // récupérer en fonction de l'année du dip
//        }
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
            'semestre' => [
                'property' => 'semestre',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by semestre',
                ],
            ],
        ];
    }
}
