<?php

namespace IntranetBundle\Dto\Enseignement;

final class AvancementEnseignementHistoriqueDto
{
    public function __construct(public array $items, public ?int $avant) {}
}
