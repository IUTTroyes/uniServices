<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261102140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Séquence facultative du plan de cours et état dans les révisions d’avancement, avec copie historique';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE intranet_avancement_enseignement ADD sequence_snapshot JSON DEFAULT NULL, ADD etat_sequence VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE intranet_avancement_enseignement DROP sequence_snapshot, DROP etat_sequence');
    }
}
