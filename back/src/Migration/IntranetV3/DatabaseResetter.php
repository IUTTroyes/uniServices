<?php

namespace App\Migration\IntranetV3;

use Doctrine\DBAL\Connection;

final readonly class DatabaseResetter
{
    private const LOCK_WAIT_TIMEOUT_SECONDS = 5;
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
    /**
     * @param null|callable(string): void $onTableReset
     */
    public function reset(?callable $onTableReset = null): int
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
            return $this->resetMySql($tables, $onTableReset);
        }

        throw new \RuntimeException(sprintf(
            'Database reset is not implemented for platform "%s". Refusing to delete data.',
            $platform,
        ));
    }

    /**
     * @param list<string> $tables
     * @param null|callable(string): void $onTableReset
     */
    private function resetMySql(array $tables, ?callable $onTableReset): int
    {
        // TRUNCATE takes a metadata lock. Without a session timeout, another
        // connection using a table can make this command appear frozen forever.
        $this->connection->executeStatement(sprintf(
            'SET SESSION lock_wait_timeout = %d',
            self::LOCK_WAIT_TIMEOUT_SECONDS,
        ));
        $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');

        try {
            foreach ($tables as $table) {
                if (null !== $onTableReset) {
                    $onTableReset($table);
                }

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
