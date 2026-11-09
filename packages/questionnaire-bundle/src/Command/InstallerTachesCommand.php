<?php

namespace QuestionnaireBundle\Command;

use App\Scheduler\AbstractInstallerTachesCommand;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Crée (sans doublon) les tâches planifiées du package. À lancer une fois par environnement après déploiement.
 * Les résumés de notifications relèvent du cœur : app:notifications:taches.
 */
#[AsCommand(
    name: 'questionnaire:taches:installer',
    description: 'Crée (sans doublon) les tâches planifiées des questionnaires : relances automatiques et clôture avec bilan.',
)]
final class InstallerTachesCommand extends AbstractInstallerTachesCommand
{
    protected function taches(): array
    {
        return [
            'Questionnaires — relances automatiques' => ['app:questionnaire:send-reminders', [], '0 8 * * 1-5', 'Chaque jour ouvré : relance les participants qui n\'ont pas répondu (au plus tous les 3 jours).'],
            'Questionnaires — clôture et bilans' => ['app:questionnaire:check-completed', [], '30 * * * *', 'Chaque heure : clôture les questionnaires terminés, envoie les bilans et publie les résultats en fin de relecture.'],
            'Questionnaires — purge des réponses' => ['questionnaire:purger', [], '0 3 1 * *', 'Chaque mois : archive les statistiques puis supprime les réponses des questionnaires clôturés au-delà de la durée de conservation.'],
        ];
    }
}
