<?php

namespace QuestionnaireBundle\Domain\Questionnaire\Repeat;

use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireSection;
use QuestionnaireBundle\Enum\QuestTypeRepeatEnum;

interface RepeatItemsProviderInterface
{
    public function supports(QuestTypeRepeatEnum $source): bool;

    /** @return list<RepeatItemView> */
    public function getItems(Questionnaire $questionnaire, QuestionnaireSection $section): array;
}
