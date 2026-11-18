<?php

declare(strict_types=1);

namespace App\Command;

use App\AnneeUniversitaire\{ContexteAnnuel, PerimetreAnnuelRegistry};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\RouterInterface;

#[AsCommand(name: 'app:annees-universitaires:audit', description: 'Vérifie la déclaration des politiques annuelles et tous les chemins ORM, sans lire les données.')]
final class AuditContexteAnnuelCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly PerimetreAnnuelRegistry $registry, private readonly RouterInterface $router) { parent::__construct(); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $errors = [];
        $annual = $operations = 0;
        foreach ($this->em->getMetadataFactory()->getAllMetadata() as $metadata) {
            try {
                $path = $this->registry->path($metadata->name);
                if ($path === null) continue;
                ++$annual;
                $owner = $metadata;
                foreach (explode('.', $path) as $field) $owner = $this->em->getClassMetadata($owner->getAssociationTargetClass($field));
                if ($owner->name !== \App\Entity\Structure\StructureAnneeUniversitaire::class) $errors[] = 'Chemin incorrect : '.$metadata->name;
            } catch (\Throwable $e) { $errors[] = $e->getMessage(); }
        }
        foreach ($this->router->getRouteCollection() as $route) {
            if (!str_starts_with($route->getPath(), '/api/')) continue;
            if ($class = $route->getDefault('_api_resource_class')) {
                try { $this->registry->path($class); ++$operations; } catch (\Throwable $e) { $errors[] = $e->getMessage(); }
                continue;
            }
            $controller = $route->getDefault('_controller');
            if (!is_string($controller)) continue;
            [$class, $method] = str_contains($controller, '::') ? explode('::', $controller, 2) : [$controller, '__invoke'];
            if (!class_exists($class) || !in_array(explode('\\', $class)[0], ['App', 'IntranetBundle', 'MessageBundle', 'QuestionnaireBundle', 'StageBundle', 'FinanceBundle', 'UnifolioBundle'], true)) continue;
            $reflection = new \ReflectionMethod($class, $method);
            $attributes = $reflection->getAttributes(ContexteAnnuel::class) ?: $reflection->getDeclaringClass()->getAttributes(ContexteAnnuel::class);
            if ($attributes === []) $errors[] = 'Politique absente : '.$class.'::'.$method;
            else { $attributes[0]->newInstance(); ++$operations; }
        }
        if ($errors !== []) { $io->error(array_values(array_unique($errors))); return Command::FAILURE; }
        $io->success(sprintf('%d chemins annuels et %d opérations HTTP déclarées vérifiés.', $annual, $operations));
        return Command::SUCCESS;
    }
}
