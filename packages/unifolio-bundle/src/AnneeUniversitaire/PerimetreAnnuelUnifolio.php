<?php

declare(strict_types=1);

namespace UnifolioBundle\AnneeUniversitaire;

use App\Extension\AbstractPerimetreAnnuelProvider;
use UnifolioBundle\Entity\Evaluation;

final class PerimetreAnnuelUnifolio extends AbstractPerimetreAnnuelProvider
{
    public function chemins(): array
    {
        return [
            'Campagne' => 'anneeUniversitaire',
            'CampagneAffectation' => 'campagne.anneeUniversitaire',
            'Portfolio' => 'scolarite.anneeUniversitaire',
            'PortfolioPage' => 'portfolio.scolarite.anneeUniversitaire',
            'PortfolioPublication' => 'portfolio.scolarite.anneeUniversitaire',
            'Trace' => 'scolarite.anneeUniversitaire',
            'TraceMedia' => 'trace.scolarite.anneeUniversitaire',
            'TraceRattachement' => 'trace.scolarite.anneeUniversitaire',
            'Commentaire' => 'trace.scolarite.anneeUniversitaire',
            'AutoEvaluation' => 'scolarite.anneeUniversitaire',
            // Nom complet : « Evaluation » est trop générique pour être déclaré par nom court.
            Evaluation::class => 'scolarite.anneeUniversitaire',
        ];
    }

    public function independants(): array
    {
        return [
            'Cv', 'CvFormation', 'CvExperience', 'Grille', 'GrilleVersion', 'GrilleCritere', 'GrilleEchelon', 'PortfolioModele',
            'ParametrageFormation', 'MigrationV1Lien', 'ReponseCritere',
        ];
    }

    public function consultablesSurToutesLesAnnees(): array
    {
        return ['Portfolio', 'Trace', 'Campagne'];
    }

    public function espaceDeNoms(): string
    {
        return 'UnifolioBundle';
    }
}
