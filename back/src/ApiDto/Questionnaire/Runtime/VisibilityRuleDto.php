<?php

namespace App\ApiDto\Questionnaire\Runtime;

final class VisibilityRuleDto
{
    public function __construct(
        public int    $dependsOnQuestionId,
        public string $operator,
        public mixed  $value
    )
    {
    }
}
