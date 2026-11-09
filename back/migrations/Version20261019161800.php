<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261019161800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add reminders_history JSON column to questionnaire_invitation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE questionnaire_invitation ADD reminders_history JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE questionnaire_invitation DROP reminders_history');
    }
}
