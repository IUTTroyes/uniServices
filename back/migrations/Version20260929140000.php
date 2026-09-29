<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260929140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add temporary V3 old_id to structure_calendrier for idempotent migration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE structure_calendrier ADD old_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_STRUCTURE_CALENDRIER_OLD_ID ON structure_calendrier (old_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_STRUCTURE_CALENDRIER_OLD_ID ON structure_calendrier');
        $this->addSql('ALTER TABLE structure_calendrier DROP old_id');
    }
}
