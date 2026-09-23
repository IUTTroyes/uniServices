<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260923150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow V3 historical records without artificial PN snapshots and preserve their legacy context';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE etudiant_scolarite_semestre ADD legacy_context JSON DEFAULT NULL, CHANGE semestre_id semestre_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE scol_evaluation ADD legacy_context JSON DEFAULT NULL, CHANGE enseignement_id enseignement_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE etudiant_scolarite_semestre DROP legacy_context, CHANGE semestre_id semestre_id INT NOT NULL');
        $this->addSql('ALTER TABLE scol_evaluation DROP legacy_context, CHANGE enseignement_id enseignement_id INT NOT NULL');
    }
}
