<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Stages : stockage réel du rapport (chemin + date de dépôt) et SIRET normalisés (rapprochement des entreprises).
 */
final class Version20261107221804 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Stages : rapport de stage stocké (report_path, report_date) et SIRET sans espaces';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stage_etudiant ADD report_path VARCHAR(255) DEFAULT NULL, ADD report_date DATETIME(6) DEFAULT NULL');
        // Aucun fichier n'a jamais été stocké : un « rapport déposé » sans fichier n'existe pas.
        $this->addSql('UPDATE stage_etudiant SET report_uploaded = 0, report_name = NULL WHERE report_path IS NULL');
        $this->addSql("UPDATE stage_entreprise SET siret = NULLIF(REPLACE(REPLACE(REPLACE(siret, ' ', ''), '.', ''), '-', ''), '') WHERE siret IS NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stage_etudiant DROP report_path, DROP report_date');
    }
}
