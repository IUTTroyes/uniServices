<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261124090000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE parcauto_reservation ADD urgence TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE parcauto_journal_vehicule ADD reservation_id INT DEFAULT NULL, ADD INDEX idx_parcauto_journal_reservation (reservation_id), ADD CONSTRAINT fk_parcauto_journal_reservation FOREIGN KEY (reservation_id) REFERENCES parcauto_reservation (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE parcauto_journal_vehicule DROP FOREIGN KEY fk_parcauto_journal_reservation');
        $this->addSql('ALTER TABLE parcauto_journal_vehicule DROP reservation_id');
        $this->addSql('ALTER TABLE parcauto_reservation DROP urgence');
    }
}
