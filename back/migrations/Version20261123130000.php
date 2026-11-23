<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261123130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Conserve les absences historiques sans événement EDT dans legacy_context.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE etudiant_absence MODIFY event_id INT DEFAULT NULL, ADD legacy_context JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf((int) $this->connection->fetchOne('SELECT COUNT(*) FROM etudiant_absence WHERE event_id IS NULL') > 0,
            'Impossible de remettre event_id NOT NULL : des absences V3 sans EDT existent.');
        $this->addSql('ALTER TABLE etudiant_absence MODIFY event_id INT NOT NULL, DROP legacy_context');
    }
}
