<?php

declare(strict_types=1);

namespace IntranetBundle\Service\Previsionnel;

use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Extension\AffectationsEnseignementInterface;
use Doctrine\ORM\EntityManagerInterface;
use IntranetBundle\Entity\Previsionnel\Previsionnel;

/** Les affectations du cœur sont celles du prévisionnel. */
final class AffectationsPrevisionnel implements AffectationsEnseignementInterface
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function personnels(ScolEnseignement $enseignement, ?StructureAnneeUniversitaire $annee): array
    {
        $criteres = ['enseignement' => $enseignement];
        if ($annee !== null) {
            $criteres['anneeUniversitaire'] = $annee;
        }

        $personnels = [];
        foreach ($this->em->getRepository(Previsionnel::class)->findBy($criteres, ['referent' => 'DESC']) as $previsionnel) {
            $personnel = $previsionnel->getPersonnel();
            if ($personnel !== null) {
                $personnels[spl_object_id($personnel)] ??= $personnel;
            }
        }

        return array_values($personnels);
    }

    public function dqlEnseignements(string $alias, string $parametrePersonnel, ?string $parametreAnnee = null): string
    {
        $dql = sprintf('SELECT IDENTITY(%1$s.enseignement) FROM %2$s %1$s WHERE %1$s.personnel = :%3$s', $alias, Previsionnel::class, $parametrePersonnel);

        return $parametreAnnee === null ? $dql : $dql.sprintf(' AND %s.anneeUniversitaire = :%s', $alias, $parametreAnnee);
    }

    public function dqlPersonnels(string $alias, string $parametreEnseignement): string
    {
        return sprintf('SELECT IDENTITY(%1$s.personnel) FROM %2$s %1$s WHERE %1$s.enseignement = :%3$s', $alias, Previsionnel::class, $parametreEnseignement);
    }

    public function dqlAffectation(string $alias, string $parametrePersonnel, string $enseignement, string $annee): string
    {
        return sprintf(
            'SELECT 1 FROM %2$s %1$s WHERE %1$s.personnel = :%3$s AND %1$s.enseignement = %4$s AND %1$s.anneeUniversitaire = %5$s',
            $alias, Previsionnel::class, $parametrePersonnel, $enseignement, $annee,
        );
    }
}
