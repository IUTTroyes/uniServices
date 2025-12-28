<?php

namespace App\ApiDto\Questionnaire\Runtime;

final class SectionIndexDto
{
    public function __construct(
        public int $publishedSectionInstanceId,
        public string $title,
        public int $questionCount,
        public int $order,
        public ?string $repeatItemType,
        public ?string $repeatItemId
    ) {}
}
