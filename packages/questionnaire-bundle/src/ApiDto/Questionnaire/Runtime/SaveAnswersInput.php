<?php

namespace QuestionnaireBundle\ApiDto\Questionnaire\Runtime;

/**
 * Corps de PATCH /api/invitations/{token}/answers :
 * {"publishedSectionInstanceId": 12, "answers": [{"questionId": 34, "value": …}, …]}.
 */
final class SaveAnswersInput
{
    public ?int $publishedSectionInstanceId = null;

    /** @var list<array{questionId?: int|string, value?: mixed}> réponses telles qu'envoyées (tableaux, non typés) */
    public array $answers = [];
}
