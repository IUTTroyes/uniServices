<?php

namespace App\Services\Dashboard\Provider;

use App\Domain\Dashboard\WidgetDefinition;
use App\Domain\Dashboard\WidgetProviderInterface;

class QuestionnaireWidgetProvider implements WidgetProviderInterface
{
    public function getBundleCode(): string
    {
        return 'questionnaire';
    }

    public function getBundleLabel(): string
    {
        return 'Questionnaire';
    }

    public function getWidgets(): array
    {
        return [
            new WidgetDefinition('questionnaire.pending', 'questionnaire', 'Questionnaires en attente', 'pi pi-inbox', 'QuestionnairePendingWidget', 'medium', true),
            new WidgetDefinition('questionnaire.stats', 'questionnaire', 'Statistiques questionnaires', 'pi pi-chart-bar', 'QuestionnaireStatsWidget', 'small', true),
            new WidgetDefinition('questionnaire.last_answers', 'questionnaire', 'Dernières réponses', 'pi pi-history', 'QuestionnaireLastAnswersWidget', 'large', false),
        ];
    }
}
