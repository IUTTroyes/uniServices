<?php

namespace App;

use App\Notification\NotificationTypeProviderInterface;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function build(ContainerBuilder $container): void
    {
        // Tout service (cœur ou package) qui implémente l'interface est enregistré automatiquement comme source de types
        // de notification : un package n'a rien à déclarer dans sa configuration.
        $container->registerForAutoconfiguration(NotificationTypeProviderInterface::class)->addTag('app.notification_type_provider');
    }
}
