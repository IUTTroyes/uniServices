<?php

namespace StageBundle\DependencyInjection;

use App\Extension\AbstractPackageExtension;

class StageExtension extends AbstractPackageExtension
{
    protected function espaceTwig(): ?string
    {
        return 'Stage';
    }

    protected function declareRessourcesApi(): bool
    {
        return true;
    }
}
