<?php

namespace IntranetBundle\Commission\Contract;

use App\Entity\Structure\StructureSemestre;
use IntranetBundle\Commission\Dto\CommissionView;

interface CommissionServiceInterface
{
    public function getCommissionView(StructureSemestre $semestre): CommissionView;
}
