<?php

namespace QuestionnaireBundle\Services\Dashboard\Provider;

use App\Domain\Dashboard\WidgetDataProviderInterface;
use App\Entity\Users\Personnel;
use QuestionnaireBundle\Repository\Questionnaires\QuestionnaireRepository;
use QuestionnaireBundle\Enum\QuestStatutEnum;

class QuestionnaireWidgetDataProvider implements WidgetDataProviderInterface
{
    public function __construct(
        private readonly QuestionnaireRepository $questionnaireRepository
    ) {}

    public function supports(string $code): bool
    {
        return str_starts_with($code, 'questionnaire.');
    }

    public function getData(string $code, Personnel $user): array
    {
        return match ($code) {
            'questionnaire.pending' => [
                // List the 5 most recently published questionnaires
                'items' => array_map(
                    fn($q) => $q->getTitle(),
                    $this->questionnaireRepository->findBy(
                        ['status' => QuestStatutEnum::PUBLISHED],
                        ['publishedAt' => 'DESC'],
                        5
                    )
                ),
            ],
            'questionnaire.stats' => [
                'total' => $this->questionnaireRepository->count([]),
                'published' => $this->questionnaireRepository->count(['status' => QuestStatutEnum::PUBLISHED]),
                'draft' => $this->questionnaireRepository->count(['status' => QuestStatutEnum::DRAFT]),
                'closed' => $this->questionnaireRepository->count(['status' => QuestStatutEnum::CLOSED]),
            ],
            'questionnaire.last_answers' => [
                // List the 5 most recently created questionnaires
                'items' => array_map(
                    fn($q) => $q->getTitle() . ' (' . ($q->getCreated()?->format('d/m H:i') ?? 'N/A') . ')',
                    $this->questionnaireRepository->findBy(
                        [],
                        ['created' => 'DESC'],
                        5
                    )
                ),
            ],
            default => [],
        };
    }
}
