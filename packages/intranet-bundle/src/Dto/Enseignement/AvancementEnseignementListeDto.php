<?php

namespace IntranetBundle\Dto\Enseignement;

final class AvancementEnseignementListeDto
{
    public function __construct(
        public int $anneeUniversitaire,
        public ?string $anneeLibelle,
        public array $sequences,
        public array $groupes,
    ) {}
}
