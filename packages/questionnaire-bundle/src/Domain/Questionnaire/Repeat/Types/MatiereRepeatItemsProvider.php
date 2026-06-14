<?php

namespace QuestionnaireBundle\Domain\Questionnaire\Repeat\Types;

use QuestionnaireBundle\Domain\Questionnaire\Repeat\RepeatItemsProviderInterface;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireSection;
use QuestionnaireBundle\Enum\QuestTypeRepeatEnum;


final class MatiereRepeatItemsProvider implements RepeatItemsProviderInterface
{
    public function supports(QuestTypeRepeatEnum $source): bool { return $source === QuestTypeRepeatEnum::MATIERE; }

    public function getItems(Questionnaire $questionnaire, QuestionnaireSection $section): array
    {
        // TODO: récupérer via repository + repeatConfig
        // return [new RepeatItemView('matiere', '12', 'Maths')];
        return [];
    }
}
