<?php

namespace QuestionnaireBundle\Domain\Questionnaire\Structure;

use QuestionnaireBundle\Domain\Questionnaire\Repeat\RepeatItemsProviderRegistry;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireSection;
use QuestionnaireBundle\Enum\QuestTypeSectionEnum;

final class QuestionnaireStructureService
{
    public function __construct(private readonly RepeatItemsProviderRegistry $repeatRegistry) {}

    /**
     * Plan des sections réelles.
     * @return list<array{sectionTemplate: QuestionnaireSection, title: string, repeatItemType: ?string, repeatItemId: ?string, order: int}>
     */
    public function buildPlan(Questionnaire $q): array
    {
        $plan = [];
        $order = 1;

        foreach ($q->getSections() as $st) {
            if ($st->getTypeSection() === QuestTypeSectionEnum::normal) {
                $plan[] = [
                    'sectionTemplate' => $st,
                    'title' => $st->getTitle(),
                    'repeatItemType' => null,
                    'repeatItemId' => null,
                    'sortOrder' => $order++,
                ];
                continue;
            }

            $items = $this->repeatRegistry->getItems($q, $st);
            foreach ($items as $item) {
                $plan[] = [
                    'sectionTemplate' => $st,
                    'title' => sprintf('%s – %s', $st->getTitle(), $item->label),
                    'repeatItemType' => $item->type,
                    'repeatItemId' => $item->id,
                    'sortOrder' => $order++,
                ];
            }
        }

        return $plan;
    }
}
