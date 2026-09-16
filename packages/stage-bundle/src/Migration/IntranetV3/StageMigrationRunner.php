<?php

namespace StageBundle\Migration\IntranetV3;

use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Migration\IntranetV3\Contract\MigratorInterface;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;
use Doctrine\ORM\EntityManagerInterface;

final class StageMigrationRunner
{
    /** @var array<string, MigratorInterface> */
    private array $migrators;

    public function __construct(
        StagePeriodeMigrator $periodeMigrator,
        StageEtudiantMigrator $etudiantMigrator,
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->migrators = [
            $periodeMigrator->getName() => $periodeMigrator,
            $etudiantMigrator->getName() => $etudiantMigrator,
        ];
    }

    /** @return list<string> */
    public function names(): array { return array_keys($this->migrators); }

    /** @return array<string, int> */
    public function checkPrerequisites(): array
    {
        return [
            'annees-universitaires' => $this->entityManager->getRepository(StructureAnneeUniversitaire::class)->count([]),
            'semestres' => $this->entityManager->getRepository(StructureSemestre::class)->count([]),
            'etudiants' => $this->entityManager->getRepository(Etudiant::class)->count([]),
            'personnels' => $this->entityManager->getRepository(Personnel::class)->count([]),
        ];
    }

    /** @return array<string, MigrationResult> */
    public function run(?string $name, MigrationContext $context): array
    {
        if (null !== $name && !isset($this->migrators[$name])) throw new \InvalidArgumentException(sprintf('Migration Stage inconnue "%s".', $name));
        $connection = $this->entityManager->getConnection();
        if ($context->dryRun) $connection->beginTransaction();
        try {
            $results = []; $executed = [];
            $targets = null === $name ? array_keys($this->migrators) : [$name];
            foreach ($targets as $target) $this->runOne($target, $context, $results, $executed);
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
        $migrator = $this->migrators[$name] ?? throw new \LogicException(sprintf('Dépendance Stage "%s" non enregistrée.', $name));
        foreach ($migrator->getDependencies() as $dependencyClass) {
            $dependency = null;
            foreach ($this->migrators as $candidate) if ($candidate instanceof $dependencyClass) { $dependency = $candidate; break; }
            if (null === $dependency) throw new \LogicException(sprintf('Dépendance Stage "%s" non enregistrée.', $dependencyClass));
            $this->runOne($dependency->getName(), $context, $results, $executed);
        }
        $context->migrationStarted($name);
        try { $results[$name] = $migrator->migrate($context); }
        finally { $context->finishProgress(); $context->migrationFinished($name); }
        $executed[$name] = true;
    }
}
