<?php

namespace IntranetBundle\Commission\Dto;

final readonly class CommissionColumn implements \JsonSerializable
{
    public const TYPE_UE = 'ue';
    public const TYPE_ENSEIGNEMENT = 'enseignement';

    /**
     * @param array<array<string, mixed>>|null $ues Rattachements UE pour les ressources/SAÉ
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $type,
        public ?string $parentKey = null,
        public ?float $coefficient = null,
        public ?float $ects = null,
        public ?string $sublabel = null,
        public ?string $nature = null,
        public ?int $enseignementId = null,
        public ?array $ues = null,
        public ?string $color = null,
        public ?string $competenceNomCourt = null,
        public ?string $competenceCouleur = null,
    ) {
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
