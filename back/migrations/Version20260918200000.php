<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260918200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Restore V3 diploma type structural metadata';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE structure_type_diplome ADD nb_semestres INT DEFAULT 2 NOT NULL, ADD niveau_entree INT DEFAULT 0 NOT NULL, ADD niveau_sortie INT DEFAULT 3 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE structure_type_diplome DROP nb_semestres, DROP niveau_entree, DROP niveau_sortie');
    }
}
