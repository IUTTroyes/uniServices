<?php

namespace IntranetBundle\Dto\Enseignement;

use Symfony\Component\Validator\Constraints as Assert;

/** Seules les informations de saisie sont désérialisables, jamais l'auteur ou le snapshot. */
final class AvancementEnseignementSaisieDto
{
    #[Assert\Length(max: 2000)]
    public string $repere = '';

    #[Assert\Length(max: 2000)]
    public string $prochaineEtape = '';

    #[Assert\NotNull, Assert\PositiveOrZero]
    public ?int $versionId = null;

    #[Assert\Positive]
    public ?int $sequenceId = null;

    #[Assert\Choice(choices: ['en_cours', 'terminee'])]
    public ?string $etatSequence = null;

    public function toArray(): array
    {
        return [
            'repere' => $this->repere, 'prochaineEtape' => $this->prochaineEtape,
            'versionId' => $this->versionId, 'sequenceId' => $this->sequenceId, 'etatSequence' => $this->etatSequence,
        ];
    }
}
