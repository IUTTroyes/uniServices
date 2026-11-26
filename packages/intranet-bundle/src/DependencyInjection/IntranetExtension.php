<?php

namespace IntranetBundle\DependencyInjection;

use App\Extension\AbstractPackageExtension;

class IntranetExtension extends AbstractPackageExtension
{
    protected function espaceTwig(): ?string
    {
        return 'Intranet';
    }
}
