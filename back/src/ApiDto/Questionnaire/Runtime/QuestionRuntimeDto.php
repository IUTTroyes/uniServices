<?php

namespace App\ApiDto\Questionnaire\Runtime;


use App\Enum\QuestTypeQuestionEnum;

final class QuestionRuntimeDto
{
    /** @param list<ChoiceDto>|null $choices */
    public function __construct(
        public int                   $questionId,
        public QuestTypeQuestionEnum $typeQuestion,
        public string                $label,
        public bool                  $required,
        public mixed                 $answer,
        public ?array                $choices = null,
        public ?ScaleDto             $scale = null,
        public ?VisibilityRuleDto    $visibility = null
    )
    {
    }
}
