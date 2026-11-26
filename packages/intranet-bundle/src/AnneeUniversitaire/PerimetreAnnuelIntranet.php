<?php

declare(strict_types=1);

namespace IntranetBundle\AnneeUniversitaire;

use App\Extension\AbstractPerimetreAnnuelProvider;

final class PerimetreAnnuelIntranet extends AbstractPerimetreAnnuelProvider
{
    public function chemins(): array
    {
        return [
            'EdtAppel' => 'edtEvent.anneeUniversitaire',
            'Previsionnel' => 'anneeUniversitaire',
            'EtudiantAbsence' => 'scolariteSemestre.scolarite.anneeUniversitaire',
            'EtudiantAbsenceJustificatif' => 'scolariteSemestre.scolarite.anneeUniversitaire',
            'MoyenneUe' => 'etudiantScolariteSemestre.scolarite.anneeUniversitaire',
            'MoyenneEnseignement' => 'etudiantScolariteSemestre.scolarite.anneeUniversitaire',
            'PlanCours' => 'anneeUniversitaire',
            'PlanCoursRealise' => 'planCours.anneeUniversitaire',
            'PlanCoursSequence' => 'planCours.anneeUniversitaire',
            'AvancementEnseignement' => 'anneeUniversitaire',
            'SousCommission' => 'semestre.annee.pn.anneeUniversitaire',
            'SousCommissionRevision' => 'sousCommission.semestre.annee.pn.anneeUniversitaire',
            'ResultatSousCommission' => 'inscription.scolarite.anneeUniversitaire',
        ];
    }

    public function espaceDeNoms(): string
    {
        return 'IntranetBundle';
    }
}
