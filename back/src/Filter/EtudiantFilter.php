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
            $departementAlias = $queryNameGenerator->generateJoinAlias('departement');
            $etudiantScolaritesAlias = $queryNameGenerator->generateJoinAlias('etudiantScolarites');
            $queryBuilder
                ->join("$alias.etudiantScolarites", $etudiantScolaritesAlias)
                ->join("$etudiantScolaritesAlias.departement", $departementAlias)
                ->andWhere("$departementAlias.id = :departement")
                ->setParameter("departement", $value);
        }

        if ('anneeUniversitaire' === $property) {
            $anneeUniversitaireAlias = $queryNameGenerator->generateJoinAlias('structureAnneeUniversitaire');
            $etudiantScolaritesAlias = $queryNameGenerator->generateJoinAlias('etudiantScolarites');
            $queryBuilder
                ->join("$alias.etudiantScolarites", $etudiantScolaritesAlias)
                ->join("$etudiantScolaritesAlias.structureAnneeUniversitaire", $anneeUniversitaireAlias)
                ->andWhere("$anneeUniversitaireAlias.id = :anneeUniversitaire")
                ->setParameter("anneeUniversitaire", $value);

            // si il y a une annee on filtre aussi
            if (isset($context['filters']['annee'])) {
                $anneeAlias = $queryNameGenerator->generateJoinAlias('structureAnnee');
                $queryBuilder
                    ->join("$etudiantScolaritesAlias.structure_annee", $anneeAlias)
                    ->andWhere("$anneeAlias.id = :annee")
                    ->setParameter("annee", $context['filters']['annee']);
            }
        }

        if ('semestre' === $property) {
            $semestreAlias = $queryNameGenerator->generateJoinAlias('structureSemestre');
            $scolariteSemestreAlias = $queryNameGenerator->generateJoinAlias('scolariteSemestre');
            $etudiantScolaritesAlias = $queryNameGenerator->generateJoinAlias('etudiantScolarites');
            $queryBuilder
                ->join("$alias.etudiantScolarites", $etudiantScolaritesAlias)
                ->join("$etudiantScolaritesAlias.scolarite_semestre", $scolariteSemestreAlias)
                ->join("$scolariteSemestreAlias.structure_semestre", $semestreAlias)
                ->andWhere("$semestreAlias.id = :semestre")
                ->setParameter("semestre", $value);
        }

//        if ('annee' === $property) {
//            $anneeAlias = $queryNameGenerator->generateJoinAlias('structureAnnee');
//            $etudiantScolaritesAlias = $queryNameGenerator->generateJoinAlias('etudiantScolarites');
//            $queryBuilder
//                ->join("$alias.etudiantScolarites", $etudiantScolaritesAlias)
//                ->join("$etudiantScolaritesAlias.structure_annee", $anneeAlias)
//                ->andWhere("$anneeAlias.id = :annee")
//                ->setParameter("annee", $value);
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
