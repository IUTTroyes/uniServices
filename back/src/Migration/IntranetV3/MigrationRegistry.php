<?php

namespace App\Migration\IntranetV3;

use App\Migration\IntranetV3\Contract\MigratorInterface;

final class MigrationRegistry
{
    /** @var array<string, MigratorInterface> */
    private array $migrators = [];

    /**
     * @param iterable<MigratorInterface> $migrators
     */
    public function __construct(iterable $migrators)
    {
        foreach ($migrators as $migrator) {
            $this->migrators[$migrator->getName()] = $migrator;
        }
    }

    /** @return array<string, MigratorInterface> */
    public function all(): array
    {
        return $this->migrators;
    }

    public function get(string $name): MigratorInterface
    {
        if (!isset($this->migrators[$name])) {
            throw new \InvalidArgumentException(sprintf('Unknown migration "%s".', $name));
        }

        return $this->migrators[$name];
    }
}
