<?php

namespace UnifolioBundle\Command;

use App\Scheduler\AbstractInstallerTachesCommand;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'unifolio:taches:installer',
    description: 'Crée (sans doublon) la tâche planifiée d\'UniFolio : la relance des évaluateurs (les résumés de notifications : app:notifications:taches).',
)]
final class InstallerTachesCommand extends AbstractInstallerTachesCommand
{
    protected function taches(): array
    {
        return [
            'UniFolio — relance des évaluateurs' => ['unifolio:campagnes:relancer', [], '0 7 * * 1-5', 'Rappelle les évaluations en attente à l\'approche (ou après) la limite d\'évaluation.'],
        ];
    }
}
