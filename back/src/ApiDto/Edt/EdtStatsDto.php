<?php

namespace App\ApiDto\Edt;

use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Users\Personnel;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints\Date;

class EdtStatsDto
{
    #[Groups(['edt_stats:read'])]
    protected float $totalHeures;

    public function getTotalHeures(): float
    {
        return $this->totalHeures;
    }

    public function setTotalHeures(float $totalHeures): void
    {
        $this->totalHeures = $totalHeures;
    }
}
