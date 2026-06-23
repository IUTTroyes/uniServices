<?php

namespace QuestionnaireBundle\Services\Dashboard\Provider;

use App\Domain\Dashboard\WidgetDataProviderInterface;
use App\Entity\Users\Personnel;

class QuestionnaireWidgetDataProvider implements WidgetDataProviderInterface
{
    public function supports(string $code): bool
    {
        return str_starts_with($code, 'questionnaire.');
    }

    public function getData(string $code, Personnel $user): array
    {
        return match ($code) {
            'questionnaire.pending' => [
                'items' => ['BUT1 S1', 'BUT2 S4', 'LP DEVOPS'],
            ],
            'questionnaire.stats' => [
                'completionRate' => 76,
                'responses' => 184,
            ],
            'questionnaire.last_answers' => [
                'items' => ['Mathématiques - 08:15', 'Réseaux - 09:40', 'Communication - 11:05'],
            ],
            default => [],
        };
    }
}
