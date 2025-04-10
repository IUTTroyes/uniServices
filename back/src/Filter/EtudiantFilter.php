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
        if ('annee' === $property) {
            $queryBuilder
                ->join("$alias.etudiantScolarites", "es4")
                ->join("es4.scolarite_semestre", "ss2")
                ->join("ss2.structure_semestre", "s2")
                ->join("es4.structureAnneeUniversitaire", "sau3")
                ->andWhere("s2.annee = :annee")
                ->setParameter("annee", $value);
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
            'semestre' => [
                'property' => 'semestre',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by semestre',
                ],
            ],
            'annee' => [
                'property' => 'annee',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by annee',
                ],
            ],
        ];
    }
}
