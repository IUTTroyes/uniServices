<?php

declare(strict_types=1);

namespace StageBundle\AnneeUniversitaire;

use App\Extension\AbstractPerimetreAnnuelProvider;

final class PerimetreAnnuelStage extends AbstractPerimetreAnnuelProvider
{
    public function chemins(): array
    {
        return [
            'StagePeriode' => 'anneeUniversitaire',
            'StageEtudiant' => 'stagePeriode.anneeUniversitaire',
            'StageAvenant' => 'stageEtudiant.stagePeriode.anneeUniversitaire',
            'StageSoutenance' => 'stagePeriode.anneeUniversitaire',
            'StageEvaluationEntreprise' => 'stageEtudiant.stagePeriode.anneeUniversitaire',
            'StageContexte' => 'stageEtudiant.stagePeriode.anneeUniversitaire',
            'StageEvaluationCritere' => 'stagePeriode.anneeUniversitaire',
            'StagePeriodeInterruption' => 'stagePeriode.anneeUniversitaire',
            'StagePeriodeSoutenance' => 'stagePeriode.anneeUniversitaire',
        ];
    }

    public function independants(): array
    {
        return ['StageEntreprise', 'StageContact', 'StageConventionTemplate'];
    }

    public function espaceDeNoms(): string
    {
        return 'StageBundle';
    }
}
