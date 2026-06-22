<?php

namespace App\Domain\Dashboard;

use App\Entity\Structure\StructureDepartementPersonnel;

final class DashboardContext
{
    public function __construct(
        private readonly ?int $departementId,
        private readonly ?StructureDepartementPersonnel $structureDepartementPersonnel = null,
    ) {
    }

    public function getDepartementId(): ?int
    {
        return $this->departementId;
    }

    public function hasDepartement(): bool
    {
        return null !== $this->departementId;
    }

    public function getStructureDepartementPersonnel(): ?StructureDepartementPersonnel
    {
        return $this->structureDepartementPersonnel;
    }
}
