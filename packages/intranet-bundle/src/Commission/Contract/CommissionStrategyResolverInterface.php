<?php

namespace IntranetBundle\Commission\Contract;

use App\Entity\Structure\StructureSemestre;

interface CommissionStrategyResolverInterface
{
    /**
     * @throws \IntranetBundle\Commission\Exception\CommissionNotSupportedException
     */
    public function resolve(StructureSemestre $semestre): CommissionStrategyInterface;
}
