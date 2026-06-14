<?php

namespace QuestionnaireBundle\ApiDto\Questionnaire\Runtime;

final class ChoiceDto
{
    public function __construct(
        public string $id,
        public string $text,
        public string $value,
    )
    {
    }
}
