<?php

namespace QuestionnaireBundle\ApiDto\Questionnaire\Runtime;

final class VisibilityRuleDto
{
    public function __construct(
        public int|string|null $dependsOnQuestionId = null,
        public ?string         $operator = null,
        public mixed           $value = null,
        public string          $action = 'show',
        public string          $logicalOperator = 'AND',
        public ?array          $conditions = null
    )
    {
    }
}
