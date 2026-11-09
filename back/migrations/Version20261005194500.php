<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261005194500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'gesdinet/jwt-refresh-token-bundle 3.x: add family and family_valid columns to refresh_token';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE refresh_token ADD family VARCHAR(32) DEFAULT NULL, ADD family_valid DATETIME(6) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE refresh_token DROP family, DROP family_valid');
    }
}
