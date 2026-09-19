<?php

namespace DocumentBundle\Migration\IntranetV3;

use Doctrine\DBAL\Connection;

final readonly class DocumentDatabaseResetter
{
    public function __construct(private Connection $connection) {}

    public function reset(): int
    {
        if (!in_array($this->connection->getDatabasePlatform()->getName(), ['mysql', 'mariadb'], true)) {
            throw new \RuntimeException('Le reset Document est actuellement prévu pour MySQL/MariaDB.');
        }
        $schema = $this->connection->createSchemaManager();
        $tables = ['document', 'document_category'];
        $existing = array_map(static fn ($table) => $table->getName(), $schema->listTables());
        $count = 0;
        $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach ($tables as $table) if (in_array($table, $existing, true)) { $this->connection->executeStatement('TRUNCATE TABLE '.$table); ++$count; }
        } finally {
            $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
        }
        return $count;
    }
}
