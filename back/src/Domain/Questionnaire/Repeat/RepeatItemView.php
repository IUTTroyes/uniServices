<?php

namespace App\Domain\Questionnaire\Repeat;

final class RepeatItemView
{
    public function __construct(
        public string $type,  // matiere|ressource|sae|previsionnel
        public string $id,
        public string $label
    ) {}
}
