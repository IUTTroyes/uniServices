<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260918194500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Preserve V3 bac type and semester rank';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE scol_bac ADD type_bac VARCHAR(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE etudiant_scolarite_semestre ADD rang INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE scol_bac DROP type_bac');
        $this->addSql('ALTER TABLE etudiant_scolarite_semestre DROP rang');
    }
}
