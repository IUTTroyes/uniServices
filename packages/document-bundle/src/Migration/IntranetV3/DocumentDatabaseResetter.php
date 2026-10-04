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
        $existing = array_map(static fn ($table) => $table->getName(), $schema->listTables());
        $count = 0;

        $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        try {
            // Ne jamais supprimer les documents natifs V4 : seuls les enregistrements
            // provenant de V3 sont identifiables de manière sûre par old_id.
            if (in_array('document', $existing, true)) {
                $count += $this->connection->executeStatement('DELETE FROM document WHERE old_id IS NOT NULL');
            }
            if (in_array('document_category', $existing, true)) {
                $count += $this->connection->executeStatement('DELETE FROM document_category WHERE old_id IS NOT NULL');
            }
        } finally {
            $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
        }

        return $count;
    }
}
