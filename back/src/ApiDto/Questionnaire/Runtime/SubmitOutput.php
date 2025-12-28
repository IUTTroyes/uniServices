<?php

namespace App\ApiDto\Questionnaire\Runtime;

final class SubmitOutput
{
    public function __construct(public bool $ok, public \DateTimeImmutable $submittedAt)
    {
    }
}
