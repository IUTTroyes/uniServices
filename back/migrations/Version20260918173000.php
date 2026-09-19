<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260918173000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Preserve V3 teaching metadata and APC level year order';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE scol_enseignement ADD opt JSON NOT NULL');
        $this->addSql('ALTER TABLE apc_niveau ADD ordre_annee INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE scol_enseignement DROP opt');
        $this->addSql('ALTER TABLE apc_niveau DROP ordre_annee');
    }
}
