<?php

namespace DocumentBundle\DependencyInjection;

use App\Extension\AbstractPackageExtension;

class DocumentExtension extends AbstractPackageExtension
{
    protected function declareRessourcesApi(): bool
    {
        return true;
    }
}
