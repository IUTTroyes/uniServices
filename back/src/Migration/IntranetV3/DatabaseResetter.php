<?php

namespace App\Migration\IntranetV3;

use Doctrine\DBAL\Connection;

final readonly class DatabaseResetter
{
    private const PRESERVED_TABLES = [
        'doctrine_migration_versions',
    ];

    public function __construct(private Connection $connection)
    {
    }

    /**
     * Empties application tables while preserving Doctrine's migration history/schema.
     * This is intentionally different from dropping/recreating the database: after the
     * reset, the current schema remains ready for a fresh V3 import.
     *
     * @return int number of truncated tables
     */
    public function reset(): int
    {
        $schemaManager = $this->connection->createSchemaManager();
        $tables = array_values(array_filter(
            $schemaManager->listTableNames(),
            static fn (string $table): bool => !in_array($table, self::PRESERVED_TABLES, true),
        ));

        if ([] === $tables) {
            return 0;
        }

        $platform = $this->connection->getDatabasePlatform()->getName();

        if (in_array($platform, ['mysql', 'mariadb'], true)) {
            return $this->resetMySql($tables);
        }

        throw new \RuntimeException(sprintf(
            'Database reset is not implemented for platform "%s". Refusing to delete data.',
            $platform,
        ));
    }

    /** @param list<string> $tables */
    private function resetMySql(array $tables): int
    {
        $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');

        try {
            foreach ($tables as $table) {
                $this->connection->executeStatement(
                    'TRUNCATE TABLE ' . $this->connection->quoteIdentifier($table),
                );
            }
        } finally {
            $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        }

        return count($tables);
    }
}
