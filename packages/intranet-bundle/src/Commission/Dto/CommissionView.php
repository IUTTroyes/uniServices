<?php

namespace IntranetBundle\Commission\Dto;

use IntranetBundle\Commission\Enum\CommissionModeEnum;

final readonly class CommissionView implements \JsonSerializable
{
    /**
     * @param CommissionColumn[] $columns
     * @param CommissionRow[]    $rows
     * @param array<string, mixed> $statistiques
     */
    public function __construct(
        public CommissionModeEnum $mode,
        public int $semestreId,
        public string $semestreLibelle,
        public array $columns,
        public array $rows,
        public array $statistiques = [],
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'mode' => $this->mode->value,
            'modeLibelle' => $this->mode->getLibelle(),
            'semestre' => ['id' => $this->semestreId, 'libelle' => $this->semestreLibelle],
            'columns' => $this->columns,
            'rows' => $this->rows,
            'statistiques' => $this->statistiques,
        ];
    }
}
