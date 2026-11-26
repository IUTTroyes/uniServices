<?php

namespace App\Security;

use App\Entity\Apc\ApcApprentissageCritique;
use App\Entity\Apc\ApcCompetence;
use App\Entity\Apc\ApcNiveau;
use App\Entity\Apc\ApcReferentiel;
use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Etudiant\EtudiantScolariteSemestre;
use App\Entity\Scolarite\ScolEnseignementUe;
use App\Entity\Structure\StructureAnnee;
use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureDepartementPersonnel;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructureSemestre;
use App\Extension\RattachementPerimetreInterface;

final class DepartmentScope
{
    public static function fromSubject(mixed $subject): ?StructureDepartement
    {
        // Utiliser les relations propriétaires. Un sujet sans périmètre résolu ne donne aucun droit local.
        return match (true) {
            $subject instanceof StructureDepartement => $subject,
            $subject instanceof StructureAnnee,
            $subject instanceof StructureDepartementPersonnel,
            $subject instanceof StructureDiplome,
            $subject instanceof StructureSemestre,
            $subject instanceof EtudiantScolarite,
            $subject instanceof ApcReferentiel => $subject->getDepartement(),
            $subject instanceof ScolEnseignementUe => $subject->getUe()?->getSemestre()?->getDepartement(),
            $subject instanceof RattachementPerimetreInterface => self::fromSubject($subject->getSujetDePerimetre()),
            $subject instanceof EtudiantScolariteSemestre => $subject->getScolarite()?->getDepartement(),
            $subject instanceof ApcCompetence => $subject->getReferentiel()?->getDepartement(),
            $subject instanceof ApcNiveau => self::fromSubject($subject->getCompetence()),
            $subject instanceof ApcApprentissageCritique => self::fromSubject($subject->getNiveau()),
            default => DiplomeScope::fromSubject($subject)?->getDepartement(),
        };
    }
}
