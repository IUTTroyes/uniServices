<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260918203000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Restore V3 UE coefficient';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE structure_ue ADD coefficient DOUBLE PRECISION DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE structure_ue DROP coefficient');
    }
}
