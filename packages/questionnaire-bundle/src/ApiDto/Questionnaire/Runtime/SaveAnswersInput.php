<?php

namespace QuestionnaireBundle\ApiDto\Questionnaire\Runtime;

final class SaveAnswersInput
{
    public int $publishedSectionInstanceId;

    /** @var list<AnswerInput> */
    public array $answers = [];
}
