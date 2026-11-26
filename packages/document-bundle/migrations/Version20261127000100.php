<?php

declare(strict_types=1);

namespace DocumentBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Schéma initial (MySQL) : crée les tables sur une base vide, ne touche pas à une base existante. */
final class Version20261127000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Schéma initial : DocumentBundle';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        // Base existante : la migration est enregistrée comme exécutée, sans aucune requête.
        if ($schema->hasTable('document')) {
            $this->write('Tables déjà présentes : rien à créer.');

            return;
        }

        $this->addSql('CREATE TABLE document (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, filename VARCHAR(255) NOT NULL, mime_type VARCHAR(100) NOT NULL, file_size INT NOT NULL, type VARCHAR(50) NOT NULL, author VARCHAR(150) DEFAULT NULL, version VARCHAR(20) DEFAULT NULL, tags JSON DEFAULT NULL, is_favorite TINYINT NOT NULL, visibility VARCHAR(50) NOT NULL, created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, old_id INT DEFAULT NULL, category_id INT DEFAULT NULL, departement_id INT DEFAULT NULL, INDEX IDX_D8698A7612469DE2 (category_id), INDEX IDX_D8698A76CCF9E01E (departement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE document_category (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(150) NOT NULL, icon VARCHAR(50) DEFAULT NULL, color VARCHAR(50) DEFAULT NULL, package_key VARCHAR(100) DEFAULT NULL, is_system TINYINT DEFAULT 0 NOT NULL, is_original TINYINT DEFAULT 0 NOT NULL, created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, old_id INT DEFAULT NULL, parent_id INT DEFAULT NULL, departement_id INT DEFAULT NULL, INDEX IDX_898DE898727ACA70 (parent_id), INDEX IDX_898DE898CCF9E01E (departement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A7612469DE2 FOREIGN KEY (category_id) REFERENCES document_category (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A76CCF9E01E FOREIGN KEY (departement_id) REFERENCES structure_departement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE document_category ADD CONSTRAINT FK_898DE898727ACA70 FOREIGN KEY (parent_id) REFERENCES document_category (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE document_category ADD CONSTRAINT FK_898DE898CCF9E01E FOREIGN KEY (departement_id) REFERENCES structure_departement (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('La migration initiale ne s’annule pas.');
    }
}
