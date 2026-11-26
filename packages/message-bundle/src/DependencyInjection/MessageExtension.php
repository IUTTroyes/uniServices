<?php

namespace MessageBundle\DependencyInjection;

use App\Extension\AbstractPackageExtension;

class MessageExtension extends AbstractPackageExtension
{
    protected function declareRessourcesApi(): bool
    {
        return true;
    }
}
