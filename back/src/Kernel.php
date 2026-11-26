<?php

namespace App;

use App\Extension\AffectationsEnseignementInterface;
use App\Extension\ChampsSerialisesInterface;
use App\Extension\PerimetreAnnuelProviderInterface;
use App\Extension\SuppressionBloqueeInterface;
use App\Notification\NotificationTypeProviderInterface;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private const DISTRIBUTION_COMPLETE = 'intranet';

    /** Points d'extension du cœur : un service (cœur ou package) qui implémente l'interface est enregistré seul. */
    private const EXTENSIONS = [
        NotificationTypeProviderInterface::class => 'app.notification_type_provider',
        ChampsSerialisesInterface::class => 'app.champs_serialises',
        AffectationsEnseignementInterface::class => 'app.affectations_enseignement',
        PerimetreAnnuelProviderInterface::class => 'app.perimetre_annuel',
        SuppressionBloqueeInterface::class => 'app.suppression_bloquee',
    ];

    /** Application servie : « intranet » (tous les packages) ou un fichier de config/distributions/. */
    public function getDistribution(): string
    {
        $distribution = $_SERVER['APP_DISTRIBUTION'] ?? $_ENV['APP_DISTRIBUTION'] ?? self::DISTRIBUTION_COMPLETE;

        return $distribution === '' ? self::DISTRIBUTION_COMPLETE : $distribution;
    }

    public function getCacheDir(): string
    {
        $dossier = $this->getProjectDir().'/var/cache/';
        if ($this->getDistribution() !== self::DISTRIBUTION_COMPLETE) {
            $dossier .= $this->getDistribution().'/';
        }

        return $dossier.$this->environment;
    }

    private function getBundlesPath(): string
    {
        if ($this->getDistribution() === self::DISTRIBUTION_COMPLETE) {
            return $this->getConfigDir().'/bundles.php';
        }

        $liste = $this->getConfigDir().'/distributions/'.$this->getDistribution().'.php';
        if (!is_file($liste)) {
            throw new \LogicException(sprintf('Distribution inconnue : « %s » (fichier %s absent).', $this->getDistribution(), $liste));
        }

        return $liste;
    }

    private function configureContainer(ContainerConfigurator $container): void
    {
        $config = $this->getConfigDir();
        $container->import($config.'/{packages}/*.{php,yaml}');
        $container->import($config.'/{packages}/'.$this->environment.'/*.{php,yaml}');
        $container->import($config.'/services.yaml');
        $container->import($config.'/{services}_'.$this->environment.'.yaml');
        // Réglages propres à la distribution, qui l'emportent sur les valeurs communes.
        $container->import($config.'/distributions/{'.$this->getDistribution().'}.yaml');
    }

    private function configureRoutes(RoutingConfigurator $routes): void
    {
        $config = $this->getConfigDir();
        $routes->import($config.'/{routes}/'.$this->environment.'/*.{php,yaml}');
        $routes->import($config.'/{routes}/*.{php,yaml}');
        $routes->import($config.'/routes.yaml');

        // Chaque package enregistré expose ses contrôleurs, dans l'ordre de la liste des bundles.
        $packages = \dirname($this->getProjectDir()).'/packages/';
        foreach ($this->getBundles() as $bundle) {
            $controleurs = $bundle->getPath().'/src/Controller';
            if ($bundle instanceof Bundle && str_starts_with($bundle->getPath(), $packages) && is_dir($controleurs)) {
                $routes->import(['path' => $controleurs, 'namespace' => $bundle->getNamespace().'\\Controller'], 'attribute');
            }
        }
    }

    protected function build(ContainerBuilder $container): void
    {
        foreach (self::EXTENSIONS as $interface => $tag) {
            $container->registerForAutoconfiguration($interface)->addTag($tag);
        }
    }
}
