<?php

namespace App\ApiDto\Questionnaire\Runtime;

final class SectionRuntimeDto
{
    /** @param list<QuestionRuntimeDto> $questions */
    public function __construct(
        public string  $questionnaireTitle,
        public int     $publishedSectionInstanceId,
        public string  $title,
        public ?string $repeatItemType,
        public ?string $repeatItemId,
        public array   $questions
    )
    {
    }
}
