<?php

namespace App\Initialization;

use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;
use Doctrine\ORM\EntityManagerInterface;

final class InitializationRunner
{
    /** @var array<string, InitializerInterface> */
    private array $initializers = [];

    /** @param iterable<InitializerInterface> $initializers */
    public function __construct(
        iterable $initializers,
        private readonly EntityManagerInterface $entityManager,
    ) {
        foreach ($initializers as $initializer) {
            $name = $initializer->getName();
            if (isset($this->initializers[$name])) {
                throw new \LogicException(sprintf('Initializer "%s" is registered more than once.', $name));
            }
            $this->initializers[$name] = $initializer;
        }
    }

    /** @return array<string, MigrationResult> */
    public function run(MigrationContext $context): array
    {
        $connection = $this->entityManager->getConnection();
        $ownsTransaction = $context->dryRun && !$connection->isTransactionActive();

        if ($ownsTransaction) {
            $connection->beginTransaction();
        }

        try {
            $results = [];
            foreach ($this->initializers as $name => $initializer) {
                $context->migrationStarted('init:' . $name);
                try {
                    $results['init:' . $name] = $initializer->initialize($context);
                } finally {
                    $context->finishProgress();
                    $context->migrationFinished('init:' . $name);
                }
            }

            if ($ownsTransaction && $connection->isTransactionActive()) {
                $connection->rollBack();
                $this->entityManager->clear();
            }

            return $results;
        } catch (\Throwable $e) {
            if ($ownsTransaction && $connection->isTransactionActive()) {
                $connection->rollBack();
                $this->entityManager->clear();
            }
            throw $e;
        }
    }
}
