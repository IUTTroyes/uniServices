<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261102120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Repères partagés et historique d’avancement par enseignement, groupe et année universitaire';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE intranet_avancement_enseignement (id INT AUTO_INCREMENT NOT NULL, enseignement_id INT NOT NULL, groupe_id INT NOT NULL, annee_universitaire_id INT NOT NULL, auteur_id INT DEFAULT NULL, auteur_libelle VARCHAR(255) NOT NULL, repere LONGTEXT NOT NULL, prochaine_etape LONGTEXT NOT NULL, created_at DATETIME NOT NULL, INDEX idx_avancement_perimetre (enseignement_id, annee_universitaire_id, groupe_id, id), INDEX IDX_AVANCEMENT_GROUPE (groupe_id), INDEX IDX_AVANCEMENT_ANNEE (annee_universitaire_id), INDEX IDX_AVANCEMENT_AUTEUR (auteur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE intranet_avancement_enseignement ADD CONSTRAINT FK_AVANCEMENT_ENSEIGNEMENT FOREIGN KEY (enseignement_id) REFERENCES scol_enseignement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE intranet_avancement_enseignement ADD CONSTRAINT FK_AVANCEMENT_GROUPE FOREIGN KEY (groupe_id) REFERENCES structure_groupe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE intranet_avancement_enseignement ADD CONSTRAINT FK_AVANCEMENT_ANNEE FOREIGN KEY (annee_universitaire_id) REFERENCES structure_annee_universitaire (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE intranet_avancement_enseignement ADD CONSTRAINT FK_AVANCEMENT_AUTEUR FOREIGN KEY (auteur_id) REFERENCES personnel (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE intranet_avancement_enseignement');
    }
}
