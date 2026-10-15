<?php

namespace IntranetBundle\Commission\Dto;

final readonly class CommissionColumn implements \JsonSerializable
{
    public const TYPE_UE = 'ue';
    public const TYPE_ENSEIGNEMENT = 'enseignement';

    public function __construct(
        public string $key,
        public string $label,
        public string $type,
        public ?string $parentKey = null,
        public ?float $coefficient = null,
        public ?float $ects = null,
        public ?string $sublabel = null,
    ) {
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
