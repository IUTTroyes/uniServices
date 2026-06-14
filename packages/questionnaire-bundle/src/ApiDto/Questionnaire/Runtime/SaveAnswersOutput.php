<?php

namespace QuestionnaireBundle\ApiDto\Questionnaire\Runtime;

final class SaveAnswersOutput
{
    public function __construct(public bool $ok, public \DateTimeImmutable $savedAt)
    {
    }
}
