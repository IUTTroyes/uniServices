<?php

namespace App\Migration\IntranetV3;

use App\Migration\IntranetV3\Contract\MigratorInterface;
use Doctrine\ORM\EntityManagerInterface;

final class MigrationRunner
{
    public function __construct(
        private readonly MigrationRegistry $registry,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /** @return list<string> */
    public function plan(?string $name): array
    {
        $planned = [];
        $visiting = [];

        if (null === $name) {
            foreach (array_keys($this->registry->all()) as $migrationName) {
                $this->planOne($migrationName, $planned, $visiting);
            }
        } else {
            $this->planOne($name, $planned, $visiting);
        }

        return array_keys($planned);
    }

    /**
     * @return array<string, MigrationResult>
     */
    public function run(?string $name, MigrationContext $context): array
    {
        $connection = $this->entityManager->getConnection();

        if ($context->dryRun) {
            $connection->beginTransaction();
        }

        try {
            $results = [];
            $executed = [];
            $visiting = [];

            if ($name === null) {
                foreach (array_keys($this->registry->all()) as $migrationName) {
                    $this->runOne($migrationName, $context, $results, $executed, $visiting);
                }
            } else {
                $this->runOne($name, $context, $results, $executed, $visiting);
            }

            if ($context->dryRun && $connection->isTransactionActive()) {
                $connection->rollBack();
                $this->entityManager->clear();
            }

            return $results;
        } catch (\Throwable $e) {
            if ($context->dryRun && $connection->isTransactionActive()) {
                $connection->rollBack();
                $this->entityManager->clear();
            }

            throw $e;
        }
    }

    /**
     * @param array<string, MigrationResult> $results
     * @param array<string, true> $executed
     * @param array<string, true> $visiting
     */
    private function runOne(
        string $name,
        MigrationContext $context,
        array &$results,
        array &$executed,
        array &$visiting,
    ): void {
        if (isset($executed[$name])) {
            return;
        }

        if (isset($visiting[$name])) {
            throw new \LogicException(sprintf('Circular migration dependency detected around "%s".', $name));
        }

        $visiting[$name] = true;
        $migrator = $this->registry->get($name);

        foreach ($migrator->getDependencies() as $dependencyClass) {
            $dependency = $this->findByClass($dependencyClass);
            $this->runOne($dependency->getName(), $context, $results, $executed, $visiting);
        }

        unset($visiting[$name]);
        $context->migrationStarted($name);

        try {
            $results[$name] = $migrator->migrate($context);
        } finally {
            $context->finishProgress();
            $context->migrationFinished($name);
        }

        $executed[$name] = true;
    }

    /**
     * @param array<string, true> $planned
     * @param array<string, true> $visiting
     */
    private function planOne(string $name, array &$planned, array &$visiting): void
    {
        if (isset($planned[$name])) {
            return;
        }

        if (isset($visiting[$name])) {
            throw new \LogicException(sprintf('Circular migration dependency detected around "%s".', $name));
        }

        $visiting[$name] = true;
        $migrator = $this->registry->get($name);

        foreach ($migrator->getDependencies() as $dependencyClass) {
            $dependency = $this->findByClass($dependencyClass);
            $this->planOne($dependency->getName(), $planned, $visiting);
        }

        unset($visiting[$name]);
        $planned[$name] = true;
    }

    /** @param class-string<MigratorInterface> $class */
    private function findByClass(string $class): MigratorInterface
    {
        foreach ($this->registry->all() as $migrator) {
            if ($migrator instanceof $class) {
                return $migrator;
            }
        }

        throw new \LogicException(sprintf('Migration dependency "%s" is not registered.', $class));
    }
}
