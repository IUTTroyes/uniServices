<?php

namespace App\ApiDto\Questionnaire\Preview;

final class PreviewSectionIndexDto
{
    public function __construct(
        public string  $key, // e.g. "tpl:12|matiere:45" or "tpl:12|none"
        public string  $title,
        public int     $questionCount,
        public ?string $repeatItemType,
        public ?string $repeatItemId,
        public int     $sortOrder,
    )
    {
    }
}
