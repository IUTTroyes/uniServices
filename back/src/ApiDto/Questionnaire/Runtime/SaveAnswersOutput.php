<?php

namespace App\ApiDto\Questionnaire\Runtime;

final class SaveAnswersOutput
{
    public function __construct(public bool $ok, public \DateTimeImmutable $savedAt)
    {
    }
}
