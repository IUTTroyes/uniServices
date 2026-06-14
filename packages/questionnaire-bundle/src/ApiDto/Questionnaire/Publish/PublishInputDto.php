<?php

namespace QuestionnaireBundle\ApiDto\Questionnaire\Publish;

final class PublishInputDto
{
    /** @var list<string> */
    public array $recipients = []; // emails
}
