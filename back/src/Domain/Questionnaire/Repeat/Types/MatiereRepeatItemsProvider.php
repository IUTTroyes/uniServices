<?php

namespace App\Domain\Questionnaire\Repeat\Types;

use App\Domain\Questionnaire\Repeat\RepeatItemView;
use App\Domain\Questionnaire\Repeat\RepeatItemsProviderInterface;
use App\Entity\Questionnaires\Questionnaire;
use App\Entity\Questionnaires\QuestionnaireSection;
use App\Enum\QuestTypeRepeatEnum;


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
