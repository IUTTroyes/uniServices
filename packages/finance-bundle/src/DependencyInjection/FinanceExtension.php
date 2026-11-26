<?php

namespace FinanceBundle\DependencyInjection;

use App\Extension\AbstractPackageExtension;

class FinanceExtension extends AbstractPackageExtension
{
    protected function espaceTwig(): ?string
    {
        return 'Finance';
    }
}
