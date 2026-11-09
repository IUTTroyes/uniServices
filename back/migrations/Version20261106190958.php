<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261106190958 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'UniFolio lot 2 : grilles d\'évaluation (versions, critères, échelle) et paramétrage des formations';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE unifolio_grille (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, granularite VARCHAR(20) NOT NULL, actif TINYINT DEFAULT 1 NOT NULL, created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, uuid BINARY(16) NOT NULL, type_diplome_id INT NOT NULL, departement_id INT DEFAULT NULL, diplome_id INT DEFAULT NULL, UNIQUE INDEX uniq_unifolio_grille_uuid (uuid), INDEX IDX_AAB6897D3BFB8FC7 (type_diplome_id), INDEX IDX_AAB6897DCCF9E01E (departement_id), INDEX IDX_AAB6897D26F859E2 (diplome_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE unifolio_grille_critere (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, aide LONGTEXT DEFAULT NULL, type VARCHAR(20) NOT NULL, source VARCHAR(20) DEFAULT \'saisie\' NOT NULL, poids DOUBLE PRECISION DEFAULT 1 NOT NULL, note_max DOUBLE PRECISION DEFAULT NULL, obligatoire TINYINT DEFAULT 1 NOT NULL, visible_etudiant TINYINT DEFAULT 1 NOT NULL, utilise_auto_eval TINYINT DEFAULT 1 NOT NULL, ordre INT NOT NULL, version_id INT NOT NULL, INDEX IDX_D7FD3D114BBC2705 (version_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE unifolio_grille_echelon (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(100) NOT NULL, valeur DOUBLE PRECISION NOT NULL, couleur VARCHAR(20) DEFAULT NULL, descripteur LONGTEXT DEFAULT NULL, ordre INT NOT NULL, version_id INT NOT NULL, INDEX IDX_CE0D4E444BBC2705 (version_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE unifolio_grille_version (id INT AUTO_INCREMENT NOT NULL, numero INT NOT NULL, publiee_le DATETIME(6) DEFAULT NULL, grille_id INT NOT NULL, UNIQUE INDEX uniq_unifolio_grille_version_numero (grille_id, numero), INDEX IDX_178B6E81985C2966 (grille_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE unifolio_parametrage_formation (id INT AUTO_INCREMENT NOT NULL, granularite VARCHAR(20) NOT NULL, agregation VARCHAR(20) DEFAULT \'max\' NOT NULL, created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, diplome_id INT NOT NULL, applicable_depuis_id INT NOT NULL, grille_id INT DEFAULT NULL, UNIQUE INDEX uniq_unifolio_parametrage_diplome_annee (diplome_id, applicable_depuis_id), INDEX IDX_F21CBA7926F859E2 (diplome_id), INDEX IDX_F21CBA79C486050A (applicable_depuis_id), INDEX IDX_F21CBA79985C2966 (grille_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE unifolio_grille ADD CONSTRAINT FK_AAB6897D3BFB8FC7 FOREIGN KEY (type_diplome_id) REFERENCES structure_type_diplome (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_grille ADD CONSTRAINT FK_AAB6897DCCF9E01E FOREIGN KEY (departement_id) REFERENCES structure_departement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_grille ADD CONSTRAINT FK_AAB6897D26F859E2 FOREIGN KEY (diplome_id) REFERENCES structure_diplome (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_grille_critere ADD CONSTRAINT FK_D7FD3D114BBC2705 FOREIGN KEY (version_id) REFERENCES unifolio_grille_version (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_grille_echelon ADD CONSTRAINT FK_CE0D4E444BBC2705 FOREIGN KEY (version_id) REFERENCES unifolio_grille_version (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_grille_version ADD CONSTRAINT FK_178B6E81985C2966 FOREIGN KEY (grille_id) REFERENCES unifolio_grille (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_parametrage_formation ADD CONSTRAINT FK_F21CBA7926F859E2 FOREIGN KEY (diplome_id) REFERENCES structure_diplome (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_parametrage_formation ADD CONSTRAINT FK_F21CBA79C486050A FOREIGN KEY (applicable_depuis_id) REFERENCES structure_annee_universitaire (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_parametrage_formation ADD CONSTRAINT FK_F21CBA79985C2966 FOREIGN KEY (grille_id) REFERENCES unifolio_grille (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE unifolio_grille DROP FOREIGN KEY FK_AAB6897D3BFB8FC7');
        $this->addSql('ALTER TABLE unifolio_grille DROP FOREIGN KEY FK_AAB6897DCCF9E01E');
        $this->addSql('ALTER TABLE unifolio_grille DROP FOREIGN KEY FK_AAB6897D26F859E2');
        $this->addSql('ALTER TABLE unifolio_grille_critere DROP FOREIGN KEY FK_D7FD3D114BBC2705');
        $this->addSql('ALTER TABLE unifolio_grille_echelon DROP FOREIGN KEY FK_CE0D4E444BBC2705');
        $this->addSql('ALTER TABLE unifolio_grille_version DROP FOREIGN KEY FK_178B6E81985C2966');
        $this->addSql('ALTER TABLE unifolio_parametrage_formation DROP FOREIGN KEY FK_F21CBA7926F859E2');
        $this->addSql('ALTER TABLE unifolio_parametrage_formation DROP FOREIGN KEY FK_F21CBA79C486050A');
        $this->addSql('ALTER TABLE unifolio_parametrage_formation DROP FOREIGN KEY FK_F21CBA79985C2966');
        $this->addSql('DROP TABLE unifolio_grille');
        $this->addSql('DROP TABLE unifolio_grille_critere');
        $this->addSql('DROP TABLE unifolio_grille_echelon');
        $this->addSql('DROP TABLE unifolio_grille_version');
        $this->addSql('DROP TABLE unifolio_parametrage_formation');
    }
}
