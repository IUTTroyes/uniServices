<?php

namespace StageBundle\Command;

use App\Scheduler\AbstractInstallerTachesCommand;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'stage:taches:installer',
    description: 'Crée (sans doublon) les tâches planifiées du package Stages : relances hebdomadaires et purge des anciens dossiers.',
)]
final class InstallerTachesCommand extends AbstractInstallerTachesCommand
{
    protected function taches(): array
    {
        return [
            'Stages — relances' => ['stage:relances', [], '0 7 * * 1', 'Chaque lundi : demandes en attente, conventions non revenues, rapports manquants.'],
            'Stages — purge des anciens dossiers' => ['stage:purger', [], '0 3 1 * *', 'Chaque mois : anonymise les dossiers terminés depuis plus de 5 ans et efface leurs rapports.'],
        ];
    }
}
