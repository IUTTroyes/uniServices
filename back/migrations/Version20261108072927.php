<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Stages : motif de refus, anonymisation (purge RGPD), critères d'évaluation par période et évaluations des entreprises.
 */
final class Version20261108072927 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Stages : motif de refus, anonymisation, grille et évaluations des tuteurs en entreprise';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE stage_evaluation_critere (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(150) NOT NULL, description LONGTEXT DEFAULT NULL, ordre INT DEFAULT 0 NOT NULL, stage_periode_id INT NOT NULL, INDEX IDX_27B12742BFDEDA6B (stage_periode_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE stage_evaluation_entreprise (id INT AUTO_INCREMENT NOT NULL, jeton_hash VARCHAR(64) NOT NULL, envoye_a VARCHAR(255) NOT NULL, envoye_le DATETIME(6) NOT NULL, expire_le DATETIME(6) NOT NULL, soumis_le DATETIME(6) DEFAULT NULL, evaluateur_nom VARCHAR(150) DEFAULT NULL, reponses JSON DEFAULT NULL, appreciation LONGTEXT DEFAULT NULL, note_proposee DOUBLE PRECISION DEFAULT NULL, stage_etudiant_id INT NOT NULL, UNIQUE INDEX UNIQ_E0BA120D1E1E10D6 (jeton_hash), UNIQUE INDEX UNIQ_E0BA120DC1D8C49 (stage_etudiant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE stage_evaluation_critere ADD CONSTRAINT FK_27B12742BFDEDA6B FOREIGN KEY (stage_periode_id) REFERENCES stage_periode (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stage_evaluation_entreprise ADD CONSTRAINT FK_E0BA120DC1D8C49 FOREIGN KEY (stage_etudiant_id) REFERENCES stage_etudiant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stage_etudiant ADD motif_refus LONGTEXT DEFAULT NULL, ADD anonymise_le DATETIME(6) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stage_evaluation_critere DROP FOREIGN KEY FK_27B12742BFDEDA6B');
        $this->addSql('ALTER TABLE stage_evaluation_entreprise DROP FOREIGN KEY FK_E0BA120DC1D8C49');
        $this->addSql('DROP TABLE stage_evaluation_critere');
        $this->addSql('DROP TABLE stage_evaluation_entreprise');
        $this->addSql('ALTER TABLE stage_etudiant DROP motif_refus, DROP anonymise_le');
    }
}
