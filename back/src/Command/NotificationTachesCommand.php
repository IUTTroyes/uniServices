<?php

namespace App\Command;

use App\Scheduler\AbstractInstallerTachesCommand;
use Symfony\Component\Console\Attribute\AsCommand;


#[AsCommand(
    name: 'app:notifications:taches',
    description: 'Crée (sans doublon) les tâches planifiées des résumés de notifications : quotidien (18 h) et hebdomadaire (lundi 8 h).',
)]
final class NotificationTachesCommand extends AbstractInstallerTachesCommand
{
    protected function taches(): array
    {
        return [
            'Notifications — résumé quotidien' => ['app:notifications:resume', ['--frequence=quotidien'], '0 18 * * *', 'Envoie chaque soir le résumé des notifications aux abonnés « quotidien ».'],
            'Notifications — résumé hebdomadaire' => ['app:notifications:resume', ['--frequence=hebdomadaire'], '0 8 * * 1', 'Envoie chaque lundi matin le résumé des notifications aux abonnés « hebdomadaire ».'],
        ];
    }
}
