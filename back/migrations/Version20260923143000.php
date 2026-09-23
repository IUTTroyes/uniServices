<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260923143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Keep the legacy personnel_departement id on structure_departement_personnel during V3 migration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE structure_departement_personnel ADD old_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_STRUCTURE_DEPARTEMENT_PERSONNEL_OLD_ID ON structure_departement_personnel (old_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_STRUCTURE_DEPARTEMENT_PERSONNEL_OLD_ID ON structure_departement_personnel');
        $this->addSql('ALTER TABLE structure_departement_personnel DROP old_id');
    }
}
