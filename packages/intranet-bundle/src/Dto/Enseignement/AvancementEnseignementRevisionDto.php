<?php

namespace IntranetBundle\Dto\Enseignement;

final class AvancementEnseignementRevisionDto
{
    public function __construct(
        public int $id,
        public string $repere,
        public string $prochaineEtape,
        public ?array $sequence,
        public ?string $etatSequence,
        public string $auteur,
        public string $date,
    ) {}
}
