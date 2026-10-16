<?php

namespace IntranetBundle\Commission\Service;

use App\Entity\Structure\StructureSemestre;
use IntranetBundle\Commission\Contract\CommissionStrategyInterface;
use IntranetBundle\Commission\Contract\CommissionStrategyResolverInterface;
use IntranetBundle\Commission\Exception\CommissionNotSupportedException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class CommissionStrategyResolver implements CommissionStrategyResolverInterface
{
    /**
     * @param iterable<CommissionStrategyInterface> $strategies
     */
    public function __construct(
        #[AutowireIterator(CommissionStrategyInterface::TAG)]
        private readonly iterable $strategies,
    ) {
    }

    public function resolve(StructureSemestre $semestre): CommissionStrategyInterface
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($semestre)) {
                return $strategy;
            }
        }

        throw CommissionNotSupportedException::forSemestre($semestre->getId());
    }
}
