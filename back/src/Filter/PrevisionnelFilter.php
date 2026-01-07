<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Operation;
use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Structure\StructureAnnee;
use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructurePn;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Structure\StructureUe;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

#[ApiFilter(PrevisionnelFilter::class)]
class PrevisionnelFilter extends AbstractFilter
{
    private const FILTERS = [
        'personnel' => 'personnel',
        'anneeUniversitaire' => 'anneeUniversitaire',
        'departement' => 'departement',
        'annee' => 'annee',
        'semestre' => 'semestre',
        'enseignement' => 'enseignement',
        'diplome' => 'diplome',
    ];
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (!in_array($property, self::FILTERS) || null === $value) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        if ('personnel' === $property) {
            $queryBuilder
                ->join(sprintf('%s.personnel', $alias), 'personnel')
                ->andWhere('personnel.id = :personnel')
                ->setParameter('personnel', $value)
            ;
        }

        if ('anneeUniversitaire' === $property) {
            $queryBuilder
                ->join(sprintf('%s.anneeUniversitaire', $alias), 'au')
                ->andWhere('au.id = :anneeUniversitaire')
                ->setParameter('anneeUniversitaire', $value)
            ;
        }

        if ('semestre' === $property) {
            $queryBuilder
                ->join(ScolEnseignement::class, 'se', 'WITH', sprintf('%s.enseignement = se.id', $alias))
                ->join('se.enseignementUes', 'seue')
                ->join(StructureUe::class, 'ue', 'WITH', 'seue.ue = ue.id')
                ->join(StructureSemestre::class, 'ss', 'WITH', 'ue.semestre = ss.id')
                ->andWhere('ss.id = :semestre')
                ->setParameter('semestre', $value)
            ;
        }

        if ('annee' === $property) {
            $queryBuilder
                ->join(ScolEnseignement::class, 'se2', 'WITH', sprintf('%s.enseignement = se2.id', $alias))
                ->join('se2.enseignementUes', 'seue2')
                ->join(StructureUe::class, 'ue2', 'WITH', 'seue2.ue = ue2.id')
                ->join(StructureSemestre::class, 'ss2', 'WITH', 'ue2.semestre = ss2.id')
                ->join(StructureAnnee::class, 'sa2', 'WITH', 'ss2.annee = sa2.id')
                ->andWhere('sa2.id = :annee')
                ->setParameter('annee', $value)
            ;
        }

        if ('enseignement' === $property) {
            $queryBuilder
                ->andWhere(sprintf('%s.enseignement = :enseignement', $alias))
                ->setParameter('enseignement', $value)
            ;
        }

        if ('diplome' === $property) {
            $queryBuilder
                ->join(ScolEnseignement::class, 'se', 'WITH', sprintf('%s.enseignement = se.id', $alias))
                ->join('se.enseignementUes', 'seue')
                ->join(StructureUe::class, 'ue', 'WITH', 'seue.ue = ue.id')
                ->join(StructureSemestre::class, 'ss', 'WITH', 'ue.semestre = ss.id')
                ->innerJoin(StructureAnnee::class, 'sa', 'WITH', 'ss.annee = sa.id')
                ->innerJoin(StructurePn::class, 'pn', 'WITH', 'sa.pn = pn.id')
                ->andWhere('pn.diplome = :diplome')
                ->setParameter('diplome', $value)
            ;
        }

        if ('departement' === $property) {
            $queryBuilder
                ->join(ScolEnseignement::class, 'se', 'WITH', sprintf('%s.enseignement = se.id', $alias))
                ->join('se.enseignementUes', 'seue')
                ->join(StructureUe::class, 'ue', 'WITH', 'seue.ue = ue.id')
                ->join(StructureSemestre::class, 'ss', 'WITH', 'ue.semestre = ss.id')
                ->innerJoin(StructureAnnee::class, 'sa', 'WITH', 'ss.annee = sa.id')
                ->innerJoin(StructurePn::class, 'pn', 'WITH', 'sa.pn = pn.id')
                ->innerJoin(StructureDiplome::class, 'sd', 'WITH', 'pn.diplome = sd.id')
                ->innerJoin(StructureDepartement::class, 'sde', 'WITH', 'sd.departement = sde.id')
                ->andWhere('sde.id = :departement')
                ->setParameter('departement', $value)
            ;
        }
    }

    public function getDescription(string $resourceClass): array
    {

        return [
            'personnel' => [
                'property' => 'personnel',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by personnel',
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
            'departement' => [
                'property' => 'departement',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by departement',
                ],
            ],
            'diplome' => [
                'property' => 'diplome',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by diplome',
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
            'enseignement' => [
                'property' => 'enseignement',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by enseignement',
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
