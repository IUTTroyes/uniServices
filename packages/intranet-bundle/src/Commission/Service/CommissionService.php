<?php

namespace IntranetBundle\Commission\Service;

use App\Entity\Structure\StructureSemestre;
use IntranetBundle\Commission\Contract\CommissionDataLoaderInterface;
use IntranetBundle\Commission\Contract\CommissionServiceInterface;
use IntranetBundle\Commission\Contract\CommissionStrategyResolverInterface;
use IntranetBundle\Commission\Dto\CommissionView;

final readonly class CommissionService implements CommissionServiceInterface
{
    public function __construct(
        private CommissionDataLoaderInterface $dataLoader,
        private CommissionStrategyResolverInterface $strategyResolver,
    ) {
    }

    public function getCommissionView(StructureSemestre $semestre): CommissionView
    {
        $dataset = $this->dataLoader->loadDataset($semestre);
        $strategy = $this->strategyResolver->resolve($semestre);

        return $strategy->build($semestre, $dataset);
    }
}
