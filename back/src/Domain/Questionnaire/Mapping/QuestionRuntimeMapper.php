<?php

namespace App\Domain\Questionnaire\Mapping;

use App\ApiDto\Questionnaire\Runtime\ChoiceDto;
use App\ApiDto\Questionnaire\Runtime\QuestionRuntimeDto;
use App\ApiDto\Questionnaire\Runtime\ScaleDto;
use App\ApiDto\Questionnaire\Runtime\VisibilityRuleDto;
use App\Entity\Questionnaires\QuestionnaireQuestion;
use App\Enum\QuestTypeQuestionEnum;

final class QuestionRuntimeMapper
{
    public function map(QuestionnaireQuestion $q, mixed $answerValue): QuestionRuntimeDto
    {
        $config = $q->getChoices();

        $choices = null;
        if (in_array($q->getTypeQuestion(), [QuestTypeQuestionEnum::MultipleChoice, QuestTypeQuestionEnum::SingleChoice], true)) {
            $choices = [];
            foreach ($config as $c) {
                $choices[] = new ChoiceDto((string) $c['id'], (string) $c['text'], (string) $c['value']);
            }
        }

        $scale = null;
        if ($q->getTypeQuestion() === QuestTypeQuestionEnum::Scale) {
            $scale = new ScaleDto(
                min: (int)($config['min'] ?? 1),
                max: (int)($config['max'] ?? 5),
                minLabel: $config['minLabel'] ?? null,
                maxLabel: $config['maxLabel'] ?? null,
            );
        }

        $visibility = null;
        if ($rule = $q->getConditionalRules()) {
            $visibility = new VisibilityRuleDto(
                dependsOnQuestionId: (int) $rule['dependsOnQuestionId'],
                operator: (string) $rule['operator'],
                value: $rule['value'] ?? null
            );
        }

        return new QuestionRuntimeDto(
            questionId: (int) $q->getId(),
            typeQuestion: $q->getTypeQuestion(),
            label: $q->getLabel(),
            required: $q->isObligatoire(),
            answer: $answerValue,
            choices: $choices,
            scale: $scale,
            visibility: $visibility,
        );
    }
}
