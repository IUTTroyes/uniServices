<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261106175745 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'UniFolio lot 1 : traces, médias et rattachements au référentiel APC';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE unifolio_trace (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, contexte LONGTEXT DEFAULT NULL, date_realisation DATE DEFAULT NULL, type_activite VARCHAR(30) DEFAULT NULL, conception_ia SMALLINT DEFAULT 3 NOT NULL, realisation_ia SMALLINT DEFAULT 4 NOT NULL, usage_ia_detail LONGTEXT DEFAULT NULL, statut VARCHAR(20) DEFAULT \'brouillon\' NOT NULL, deposee_le DATETIME(6) DEFAULT NULL, tags JSON NOT NULL, created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, uuid BINARY(16) NOT NULL, etudiant_id INT NOT NULL, scolarite_id INT DEFAULT NULL, enseignement_id INT DEFAULT NULL, INDEX idx_unifolio_trace_etudiant_statut (etudiant_id, statut), UNIQUE INDEX uniq_unifolio_trace_uuid (uuid), INDEX IDX_64B84AACDDEAB1A3 (etudiant_id), INDEX IDX_64B84AACAA6B2AB6 (scolarite_id), INDEX IDX_64B84AACABEC3B20 (enseignement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE unifolio_trace_media (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(20) NOT NULL, ordre INT NOT NULL, chemin VARCHAR(255) DEFAULT NULL, nom_original VARCHAR(255) DEFAULT NULL, mime_type VARCHAR(120) DEFAULT NULL, taille INT DEFAULT NULL, url VARCHAR(2048) DEFAULT NULL, contenu LONGTEXT DEFAULT NULL, legende VARCHAR(255) DEFAULT NULL, metadata JSON NOT NULL, trace_id INT NOT NULL, INDEX IDX_B91D964BE0D4B70 (trace_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE unifolio_trace_rattachement (id INT AUTO_INCREMENT NOT NULL, argumentaire LONGTEXT DEFAULT NULL, ordre INT NOT NULL, trace_id INT NOT NULL, competence_id INT DEFAULT NULL, niveau_id INT DEFAULT NULL, apprentissage_critique_id INT DEFAULT NULL, INDEX IDX_7CDAA064BE0D4B70 (trace_id), INDEX IDX_7CDAA06415761DAB (competence_id), INDEX IDX_7CDAA064B3E9C81 (niveau_id), INDEX IDX_7CDAA06462399251 (apprentissage_critique_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE unifolio_trace ADD CONSTRAINT FK_64B84AACDDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES etudiant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_trace ADD CONSTRAINT FK_64B84AACAA6B2AB6 FOREIGN KEY (scolarite_id) REFERENCES etudiant_scolarite (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE unifolio_trace ADD CONSTRAINT FK_64B84AACABEC3B20 FOREIGN KEY (enseignement_id) REFERENCES scol_enseignement (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE unifolio_trace_media ADD CONSTRAINT FK_B91D964BE0D4B70 FOREIGN KEY (trace_id) REFERENCES unifolio_trace (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_trace_rattachement ADD CONSTRAINT FK_7CDAA064BE0D4B70 FOREIGN KEY (trace_id) REFERENCES unifolio_trace (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_trace_rattachement ADD CONSTRAINT FK_7CDAA06415761DAB FOREIGN KEY (competence_id) REFERENCES apc_competence (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_trace_rattachement ADD CONSTRAINT FK_7CDAA064B3E9C81 FOREIGN KEY (niveau_id) REFERENCES apc_niveau (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_trace_rattachement ADD CONSTRAINT FK_7CDAA06462399251 FOREIGN KEY (apprentissage_critique_id) REFERENCES apc_apprentissage_critique (id) ON DELETE CASCADE');
        // Exactement une cible par rattachement (compétence, niveau ou AC).
        $this->addSql('ALTER TABLE unifolio_trace_rattachement ADD CONSTRAINT chk_unifolio_rattachement_cible CHECK ((competence_id IS NOT NULL) + (niveau_id IS NOT NULL) + (apprentissage_critique_id IS NOT NULL) = 1)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE unifolio_trace_rattachement DROP CHECK chk_unifolio_rattachement_cible');
        $this->addSql('ALTER TABLE unifolio_trace DROP FOREIGN KEY FK_64B84AACDDEAB1A3');
        $this->addSql('ALTER TABLE unifolio_trace DROP FOREIGN KEY FK_64B84AACAA6B2AB6');
        $this->addSql('ALTER TABLE unifolio_trace DROP FOREIGN KEY FK_64B84AACABEC3B20');
        $this->addSql('ALTER TABLE unifolio_trace_media DROP FOREIGN KEY FK_B91D964BE0D4B70');
        $this->addSql('ALTER TABLE unifolio_trace_rattachement DROP FOREIGN KEY FK_7CDAA064BE0D4B70');
        $this->addSql('ALTER TABLE unifolio_trace_rattachement DROP FOREIGN KEY FK_7CDAA06415761DAB');
        $this->addSql('ALTER TABLE unifolio_trace_rattachement DROP FOREIGN KEY FK_7CDAA064B3E9C81');
        $this->addSql('ALTER TABLE unifolio_trace_rattachement DROP FOREIGN KEY FK_7CDAA06462399251');
        $this->addSql('DROP TABLE unifolio_trace');
        $this->addSql('DROP TABLE unifolio_trace_media');
        $this->addSql('DROP TABLE unifolio_trace_rattachement');
    }
}
