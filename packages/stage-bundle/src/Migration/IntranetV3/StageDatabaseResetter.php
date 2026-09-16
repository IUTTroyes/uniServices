<?php

namespace StageBundle\Migration\IntranetV3;

use Doctrine\DBAL\Connection;

final readonly class StageDatabaseResetter
{
    private const TABLES = ['stage_etudiant', 'stage_periode'];

    public function __construct(private Connection $connection) {}

    public function reset(): int
    {
        $existing = array_flip($this->connection->createSchemaManager()->listTableNames());
        $tables = array_values(array_filter(self::TABLES, static fn (string $table): bool => isset($existing[$table])));
        if ([] === $tables) return 0;
        $platform = $this->connection->getDatabasePlatform()->getName();
        if (!in_array($platform, ['mysql', 'mariadb'], true)) throw new \RuntimeException(sprintf('Stage reset non implémenté pour "%s".', $platform));
        $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        try {
            foreach ($tables as $table) $this->connection->executeStatement('TRUNCATE TABLE '.$this->connection->quoteIdentifier($table));
        } finally {
            $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        }
        return count($tables);
    }
}
