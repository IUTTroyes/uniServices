<?php

namespace App\Migration\IntranetV3;

use App\Migration\IntranetV3\Contract\MigratorInterface;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

abstract class AbstractMigrator implements MigratorInterface
{
    protected const BATCH_SIZE = 200;

    public function __construct(
        #[Autowire(service: 'doctrine.dbal.copy_connection')]
        protected readonly Connection $source,
        protected readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function getDependencies(): array
    {
        return [];
    }

    protected function flush(MigrationContext $context): void
    {
        // En dry-run le runner ouvre une transaction globale puis la rollback.
        // On flush tout de même afin que les migrateurs dépendants puissent
        // retrouver les entités créées précédemment dans la même exécution.
        $this->entityManager->flush();
    }

    protected function flushAndClear(MigrationContext $context): void
    {
        $this->flush($context);
        $this->clear();
        gc_collect_cycles();
    }

    protected function flushBatch(MigrationContext $context, int $processed): void
    {
        if (1 === $processed) {
            $context->startProgress('Lignes', 0);
        }

        $context->advanceProgress();

        if ($processed > 0 && 0 === $processed % self::BATCH_SIZE) {
            $this->flushAndClear($context);
        }
    }

    protected function startProgress(MigrationContext $context, string $label, int $total): void
    {
        $context->startProgress($label, $total);
    }

    protected function finishProgress(MigrationContext $context): void
    {
        $context->finishProgress();
    }

    protected function clear(): void
    {
        $this->entityManager->clear();
    }
}
