<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260911114000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add legacy id to EDT events and store event date as a DATE.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE edt_event ADD old_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_EDT_EVENT_OLD_ID ON edt_event (old_id)');
        $this->addSql('ALTER TABLE edt_event CHANGE date date DATE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_EDT_EVENT_OLD_ID ON edt_event');
        $this->addSql('ALTER TABLE edt_event DROP old_id');
        $this->addSql('ALTER TABLE edt_event CHANGE date date TIME DEFAULT NULL');
    }
}
