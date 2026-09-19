<?php

namespace App\Migration\IntranetV3;

final readonly class MigrationContext
{
    public function __construct(
        public bool $dryRun = false,
        public bool $verbose = false,
        private ?\Closure $onMigrationStart = null,
        private ?\Closure $onMigrationFinish = null,
        private ?\Closure $onProgressStart = null,
        private ?\Closure $onProgressAdvance = null,
        private ?\Closure $onProgressFinish = null,
    ) {
    }

    public function migrationStarted(string $name): void
    {
        if (null !== $this->onMigrationStart) {
            ($this->onMigrationStart)($name);
        }
    }

    public function migrationFinished(string $name): void
    {
        if (null !== $this->onMigrationFinish) {
            ($this->onMigrationFinish)($name);
        }
    }

    public function startProgress(string $label, int $total): void
    {
        if (null !== $this->onProgressStart) {
            ($this->onProgressStart)($label, max(0, $total));
        }
    }

    public function advanceProgress(int $step = 1): void
    {
        if (null !== $this->onProgressAdvance) {
            ($this->onProgressAdvance)($step);
        }
    }

    public function finishProgress(): void
    {
        if (null !== $this->onProgressFinish) {
            ($this->onProgressFinish)();
        }
    }
}
