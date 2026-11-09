<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261107073850 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'UniFolio lot 5 : portfolios, pages à blocs, modèles de portfolio et CV';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE unifolio_cv (id INT AUTO_INCREMENT NOT NULL, poste VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, langues JSON NOT NULL, soft_skills JSON NOT NULL, hard_skills JSON NOT NULL, reseaux JSON NOT NULL, created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, etudiant_id INT NOT NULL, UNIQUE INDEX uniq_unifolio_cv_etudiant (etudiant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE unifolio_cv_experience (id INT AUTO_INCREMENT NOT NULL, poste VARCHAR(255) NOT NULL, entreprise VARCHAR(255) DEFAULT NULL, date_debut DATETIME(6) DEFAULT NULL, date_fin DATETIME(6) DEFAULT NULL, description LONGTEXT DEFAULT NULL, ordre INT NOT NULL, cv_id INT NOT NULL, INDEX IDX_98C06E02CFE419E2 (cv_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE unifolio_cv_formation (id INT AUTO_INCREMENT NOT NULL, diplome VARCHAR(255) NOT NULL, etablissement VARCHAR(255) DEFAULT NULL, date_debut DATETIME(6) DEFAULT NULL, date_fin DATETIME(6) DEFAULT NULL, description LONGTEXT DEFAULT NULL, ordre INT NOT NULL, cv_id INT NOT NULL, INDEX IDX_C2988F3ACFE419E2 (cv_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE unifolio_portfolio (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(20) NOT NULL, titre VARCHAR(255) NOT NULL, theme JSON NOT NULL, visibilite VARCHAR(20) DEFAULT \'prive\' NOT NULL, created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, uuid BINARY(16) NOT NULL, etudiant_id INT NOT NULL, scolarite_id INT DEFAULT NULL, modele_id INT DEFAULT NULL, UNIQUE INDEX uniq_unifolio_portfolio_uuid (uuid), UNIQUE INDEX uniq_unifolio_portfolio_scolarite (etudiant_id, scolarite_id, type), INDEX IDX_C39A3226DDEAB1A3 (etudiant_id), INDEX IDX_C39A3226AA6B2AB6 (scolarite_id), INDEX IDX_C39A3226AC14B70A (modele_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE unifolio_portfolio_modele (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, type VARCHAR(20) NOT NULL, actif TINYINT DEFAULT 1 NOT NULL, pages JSON NOT NULL, created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, uuid BINARY(16) NOT NULL, departement_id INT DEFAULT NULL, diplome_id INT DEFAULT NULL, UNIQUE INDEX uniq_unifolio_modele_uuid (uuid), INDEX IDX_F3A0B682CCF9E01E (departement_id), INDEX IDX_F3A0B68226F859E2 (diplome_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE unifolio_portfolio_page (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, ordre INT NOT NULL, verrouillee TINYINT DEFAULT 0 NOT NULL, blocs JSON NOT NULL, verrouilles JSON NOT NULL, version INT DEFAULT 1 NOT NULL, created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, uuid BINARY(16) NOT NULL, portfolio_id INT NOT NULL, UNIQUE INDEX uniq_unifolio_page_uuid (uuid), INDEX IDX_4DDF951EB96B5643 (portfolio_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE unifolio_cv ADD CONSTRAINT FK_A86161F9DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES etudiant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_cv_experience ADD CONSTRAINT FK_98C06E02CFE419E2 FOREIGN KEY (cv_id) REFERENCES unifolio_cv (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_cv_formation ADD CONSTRAINT FK_C2988F3ACFE419E2 FOREIGN KEY (cv_id) REFERENCES unifolio_cv (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_portfolio ADD CONSTRAINT FK_C39A3226DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES etudiant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_portfolio ADD CONSTRAINT FK_C39A3226AA6B2AB6 FOREIGN KEY (scolarite_id) REFERENCES etudiant_scolarite (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_portfolio ADD CONSTRAINT FK_C39A3226AC14B70A FOREIGN KEY (modele_id) REFERENCES unifolio_portfolio_modele (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE unifolio_portfolio_modele ADD CONSTRAINT FK_F3A0B682CCF9E01E FOREIGN KEY (departement_id) REFERENCES structure_departement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_portfolio_modele ADD CONSTRAINT FK_F3A0B68226F859E2 FOREIGN KEY (diplome_id) REFERENCES structure_diplome (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_portfolio_page ADD CONSTRAINT FK_4DDF951EB96B5643 FOREIGN KEY (portfolio_id) REFERENCES unifolio_portfolio (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_campagne ADD modele_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE unifolio_campagne ADD CONSTRAINT FK_9499EB07AC14B70A FOREIGN KEY (modele_id) REFERENCES unifolio_portfolio_modele (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_9499EB07AC14B70A ON unifolio_campagne (modele_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE unifolio_cv DROP FOREIGN KEY FK_A86161F9DDEAB1A3');
        $this->addSql('ALTER TABLE unifolio_cv_experience DROP FOREIGN KEY FK_98C06E02CFE419E2');
        $this->addSql('ALTER TABLE unifolio_cv_formation DROP FOREIGN KEY FK_C2988F3ACFE419E2');
        $this->addSql('ALTER TABLE unifolio_portfolio DROP FOREIGN KEY FK_C39A3226DDEAB1A3');
        $this->addSql('ALTER TABLE unifolio_portfolio DROP FOREIGN KEY FK_C39A3226AA6B2AB6');
        $this->addSql('ALTER TABLE unifolio_portfolio DROP FOREIGN KEY FK_C39A3226AC14B70A');
        $this->addSql('ALTER TABLE unifolio_portfolio_modele DROP FOREIGN KEY FK_F3A0B682CCF9E01E');
        $this->addSql('ALTER TABLE unifolio_portfolio_modele DROP FOREIGN KEY FK_F3A0B68226F859E2');
        $this->addSql('ALTER TABLE unifolio_portfolio_page DROP FOREIGN KEY FK_4DDF951EB96B5643');
        $this->addSql('DROP TABLE unifolio_cv');
        $this->addSql('DROP TABLE unifolio_cv_experience');
        $this->addSql('DROP TABLE unifolio_cv_formation');
        $this->addSql('DROP TABLE unifolio_portfolio');
        $this->addSql('DROP TABLE unifolio_portfolio_modele');
        $this->addSql('DROP TABLE unifolio_portfolio_page');
        $this->addSql('ALTER TABLE unifolio_campagne DROP FOREIGN KEY FK_9499EB07AC14B70A');
        $this->addSql('DROP INDEX IDX_9499EB07AC14B70A ON unifolio_campagne');
        $this->addSql('ALTER TABLE unifolio_campagne DROP modele_id');
    }
}
