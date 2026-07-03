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

            // Section configurable : on boucle sur les éléments sauvegardés dans le JSON opt
            $opts = $st->getOpt();
            $elements = $opts['elements'] ?? [];
            foreach ($elements as $el) {
                $elementName = $el['name'] ?? '';
                $elementId = $el['id'] ?? '';
                $sourceType = $opts['sourceType'] ?? 'matiere';

                // Génération du titre avec le patron
                $titleTemplate = $opts['titleTemplate'] ?? 'Évaluation de {element}';
                $title = str_replace('{element}', $elementName, $titleTemplate);

                $plan[] = [
                    'sectionTemplate' => $st,
                    'title' => $title,
                    'repeatItemType' => $sourceType,
                    'repeatItemId' => $elementId,
                    'sortOrder' => $order++,
                ];
            }
        }

        return $plan;
    }
}
