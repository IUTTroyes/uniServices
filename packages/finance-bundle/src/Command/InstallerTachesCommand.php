<?php

namespace FinanceBundle\Command;

use App\Scheduler\AbstractInstallerTachesCommand;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'finance:taches:installer',
    description: 'Crée (sans doublon) les tâches planifiées du package Finances : récapitulatif des demandes en attente.',
)]
final class InstallerTachesCommand extends AbstractInstallerTachesCommand
{
    protected function taches(): array
    {
        return [
            'Finances — récapitulatif du matin' => ['finance:recapitulatif', [], '0 8 * * 1-5', 'Jours ouvrés à 8 h : demandes d\'achat en attente, au service financier et aux valideurs.'],
            'Finances — récapitulatif de l\'après-midi' => ['finance:recapitulatif', [], '30 13 * * 1-5', 'Jours ouvrés à 13 h 30 : demandes d\'achat en attente, au service financier et aux valideurs.'],
        ];
    }
}
