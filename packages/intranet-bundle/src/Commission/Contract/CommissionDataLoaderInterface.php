<?php

namespace IntranetBundle\Commission\Contract;

use App\Entity\Structure\StructureSemestre;
use IntranetBundle\Commission\Dto\CommissionDataset;

interface CommissionDataLoaderInterface
{
    public function loadDataset(StructureSemestre $semestre): CommissionDataset;
}
