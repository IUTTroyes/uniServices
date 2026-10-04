<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260911111500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store historical absence totals on each student semester enrollment.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE etudiant_scolarite_semestre ADD nb_absences INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE etudiant_scolarite_semestre DROP nb_absences');
    }
}
