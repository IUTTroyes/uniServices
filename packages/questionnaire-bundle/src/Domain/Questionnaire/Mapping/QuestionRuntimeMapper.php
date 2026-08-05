<?php

namespace QuestionnaireBundle\Domain\Questionnaire\Mapping;

use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireQuestion;
use QuestionnaireBundle\ApiDto\Questionnaire\Runtime\ChoiceDto;
use QuestionnaireBundle\ApiDto\Questionnaire\Runtime\QuestionRuntimeDto;
use QuestionnaireBundle\ApiDto\Questionnaire\Runtime\ScaleDto;
use QuestionnaireBundle\ApiDto\Questionnaire\Runtime\VisibilityRuleDto;
use QuestionnaireBundle\Enum\QuestTypeQuestionEnum;

final class QuestionRuntimeMapper
{
    public function map(QuestionnaireQuestion $q, mixed $answerValue, ?iterable $allQuestionsInContext = null): QuestionRuntimeDto
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
        $qId = (string) $q->getId();
        $qUuid = $q->getUuid() ? (string) $q->getUuid() : null;

        $searchQuestions = [];
        if ($allQuestionsInContext !== null) {
            foreach ($allQuestionsInContext as $item) {
                if ($item instanceof QuestionnaireQuestion) {
                    $searchQuestions[] = $item;
                }
            }
        }
        if (empty($searchQuestions)) {
            $searchQuestions = [$q];
        }

        $targetedRule = null;
        foreach ($searchQuestions as $sq) {
            $rules = $sq->getConditionalRules();
            if (empty($rules) || !is_array($rules)) {
                continue;
            }

            $ruleList = isset($rules[0]) && is_array($rules[0]) ? $rules : [$rules];
            foreach ($ruleList as $r) {
                if (!is_array($r)) continue;

                $targetIds = $r['targetQuestionIds'] ?? [];
                $isTargeted = false;

                if (!empty($targetIds) && is_array($targetIds)) {
                    foreach ($targetIds as $tid) {
                        $tidStr = (string) $tid;
                        if ($tidStr === $qId || ($qUuid !== null && $tidStr === $qUuid)) {
                            $isTargeted = true;
                            break;
                        }
                    }
                } else {
                    $dep = (string) ($r['dependsOnQuestionId'] ?? $r['dependsOn'] ?? '');
                    if ($dep !== '' && $dep !== $qId && ($qUuid === null || $dep !== $qUuid) && (string)$sq->getId() === $qId) {
                        $isTargeted = true;
                    }
                }

                if ($isTargeted) {
                    $targetedRule = $r;
                    break 2;
                }
            }
        }

        if ($targetedRule !== null) {
            $conditions = [];
            $logicalOperator = (string) ($targetedRule['logicalOperator'] ?? 'AND');
            $action = (string) ($targetedRule['action'] ?? 'show');

            if (!empty($targetedRule['conditions']) && is_array($targetedRule['conditions'])) {
                foreach ($targetedRule['conditions'] as $cond) {
                    if (!is_array($cond)) continue;
                    $dep = $cond['dependsOnQuestionId'] ?? $cond['dependsOn'] ?? null;
                    if ($dep !== null) {
                        $conditions[] = [
                            'dependsOnQuestionId' => is_numeric($dep) ? (int) $dep : (string) $dep,
                            'operator' => (string) ($cond['operator'] ?? ''),
                            'value' => $cond['value'] ?? null,
                        ];
                    }
                }
            }

            // Fallback for simple condition rules
            if (empty($conditions)) {
                $dep = $targetedRule['dependsOnQuestionId'] ?? $targetedRule['dependsOn'] ?? null;
                $operator = $targetedRule['operator'] ?? null;
                if ($dep !== null && $operator !== null) {
                    $conditions[] = [
                        'dependsOnQuestionId' => is_numeric($dep) ? (int) $dep : (string) $dep,
                        'operator' => (string) $operator,
                        'value' => $targetedRule['value'] ?? null,
                    ];
                }
            }

            if (!empty($conditions)) {
                $firstCond = $conditions[0];
                $visibility = new VisibilityRuleDto(
                    dependsOnQuestionId: $firstCond['dependsOnQuestionId'],
                    operator: $firstCond['operator'],
                    value: $firstCond['value'],
                    action: $action,
                    logicalOperator: $logicalOperator,
                    conditions: $conditions
                );
            }
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
