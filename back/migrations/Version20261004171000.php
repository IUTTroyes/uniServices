<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261004171000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add temporary V3 old_id to salle for idempotent migration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE salle ADD old_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_SALLE_OLD_ID ON salle (old_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_SALLE_OLD_ID ON salle');
        $this->addSql('ALTER TABLE salle DROP old_id');
    }
}
