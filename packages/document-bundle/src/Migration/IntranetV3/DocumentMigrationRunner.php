<?php

namespace DocumentBundle\Migration\IntranetV3;

use App\Entity\Structure\StructureDepartement;
use App\Migration\IntranetV3\Contract\MigratorInterface;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;
use Doctrine\ORM\EntityManagerInterface;

final class DocumentMigrationRunner
{
    /** @var array<string, MigratorInterface> */
    private array $migrators;

    public function __construct(
        DocumentCategoryMigrator $categoryMigrator,
        DocumentMigrator $documentMigrator,
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->migrators = [
            $categoryMigrator->getName() => $categoryMigrator,
            $documentMigrator->getName() => $documentMigrator,
        ];
    }

    /** @return list<string> */
    public function names(): array { return array_keys($this->migrators); }

    /** @return array<string, int> */
    public function checkPrerequisites(): array
    {
        return ['departements' => $this->entityManager->getRepository(StructureDepartement::class)->count([])];
    }

    /** @return array<string, MigrationResult> */
    public function run(?string $name, MigrationContext $context): array
    {
        if (null !== $name && !isset($this->migrators[$name])) throw new \InvalidArgumentException(sprintf('Migration Document inconnue "%s".', $name));
        $connection = $this->entityManager->getConnection();
        if ($context->dryRun) $connection->beginTransaction();

        try {
            $results = []; $executed = [];
            foreach (null === $name ? array_keys($this->migrators) : [$name] as $target) $this->runOne($target, $context, $results, $executed);
            if ($context->dryRun && $connection->isTransactionActive()) { $connection->rollBack(); $this->entityManager->clear(); }
            return $results;
        } catch (\Throwable $e) {
            if ($context->dryRun && $connection->isTransactionActive()) { $connection->rollBack(); $this->entityManager->clear(); }
            throw $e;
        }
    }

    private function runOne(string $name, MigrationContext $context, array &$results, array &$executed): void
    {
        if (isset($executed[$name])) return;
        $migrator = $this->migrators[$name] ?? throw new \LogicException(sprintf('Dépendance Document "%s" non enregistrée.', $name));
        foreach ($migrator->getDependencies() as $dependencyClass) {
            $dependency = null;
            foreach ($this->migrators as $candidate) if ($candidate instanceof $dependencyClass) { $dependency = $candidate; break; }
            if (null === $dependency) throw new \LogicException(sprintf('Dépendance Document "%s" non enregistrée.', $dependencyClass));
            $this->runOne($dependency->getName(), $context, $results, $executed);
        }
        $context->migrationStarted($name);
        try { $results[$name] = $migrator->migrate($context); }
        finally { $context->finishProgress(); $context->migrationFinished($name); }
        $executed[$name] = true;
    }
}
