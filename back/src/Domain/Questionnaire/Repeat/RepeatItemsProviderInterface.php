<?php

namespace App\Domain\Questionnaire\Repeat;

use App\Entity\Questionnaires\Questionnaire;
use App\Entity\Questionnaires\QuestionnaireSection;
use App\Enum\QuestTypeRepeatEnum;

interface RepeatItemsProviderInterface
{
    public function supports(QuestTypeRepeatEnum $source): bool;

    /** @return list<RepeatItemView> */
    public function getItems(Questionnaire $questionnaire, QuestionnaireSection $section): array;
}
