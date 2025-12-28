<?php

namespace App\ApiDto\Questionnaire\Runtime;

final class ScaleDto
{
    public function __construct(
        public int     $min,
        public int     $max,
        public ?string $minLabel = null,
        public ?string $maxLabel = null
    )
    {
    }
}
