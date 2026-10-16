<?php

namespace IntranetBundle\Commission\Dto;

final class CommissionRow implements \JsonSerializable
{
    /**
     * @param array<string, float|null> $cells valeurs indexées par clé de colonne
     */
    public function __construct(
        public readonly int $scolariteSemestreId,
        public readonly ?int $etudiantId,
        public readonly string $nom,
        public readonly string $prenom,
        public readonly array $cells,
        public readonly ?float $moyenne,
        public ?int $rang = null,
        public readonly ?bool $decision = null,
        public readonly string $decisionLibelle = '',
        public readonly int $uesValidees = 0,
        public readonly ?string $numeroEtudiant = null,
        public readonly ?string $dateNaissance = null,
        public readonly ?string $bac = null,
    ) {
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
