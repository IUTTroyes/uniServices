<?php

declare(strict_types=1);

namespace HelpdeskBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Schéma initial (MySQL) : crée les tables sur une base vide, ne touche pas à une base existante. */
final class Version20261127000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Schéma initial : HelpdeskBundle';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        // Base existante : la migration est enregistrée comme exécutée, sans aucune requête.
        if ($schema->hasTable('helpdesk_categorie')) {
            $this->write('Tables déjà présentes : rien à créer.');

            return;
        }

        $this->addSql('CREATE TABLE helpdesk_ticket (id INT AUTO_INCREMENT NOT NULL, subject VARCHAR(50) NOT NULL, description LONGTEXT DEFAULT NULL, statut VARCHAR(20) NOT NULL, priority VARCHAR(255) DEFAULT NULL, files_names JSON DEFAULT NULL, created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, helpdesk_categorie_id INT NOT NULL, auteur_id INT NOT NULL, assigne_id INT DEFAULT NULL, INDEX IDX_828A75BB3F977D82 (helpdesk_categorie_id), INDEX IDX_828A75BB60BB6FE6 (auteur_id), INDEX IDX_828A75BB8E7B8AB0 (assigne_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE helpdesk_message (id INT AUTO_INCREMENT NOT NULL, content VARCHAR(255) NOT NULL, created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, ticket_id INT NOT NULL, auteur_id INT DEFAULT NULL, INDEX IDX_A5C482F1700047D2 (ticket_id), INDEX IDX_A5C482F160BB6FE6 (auteur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE helpdesk_categorie (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(255) NOT NULL, parent_id INT DEFAULT NULL, service_id INT NOT NULL, INDEX IDX_C912A228727ACA70 (parent_id), INDEX IDX_C912A228ED5CA9E6 (service_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE helpdesk_ticket ADD CONSTRAINT FK_828A75BB3F977D82 FOREIGN KEY (helpdesk_categorie_id) REFERENCES helpdesk_categorie (id)');
        $this->addSql('ALTER TABLE helpdesk_ticket ADD CONSTRAINT FK_828A75BB60BB6FE6 FOREIGN KEY (auteur_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE helpdesk_ticket ADD CONSTRAINT FK_828A75BB8E7B8AB0 FOREIGN KEY (assigne_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE helpdesk_message ADD CONSTRAINT FK_A5C482F1700047D2 FOREIGN KEY (ticket_id) REFERENCES helpdesk_ticket (id)');
        $this->addSql('ALTER TABLE helpdesk_message ADD CONSTRAINT FK_A5C482F160BB6FE6 FOREIGN KEY (auteur_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE helpdesk_categorie ADD CONSTRAINT FK_C912A228727ACA70 FOREIGN KEY (parent_id) REFERENCES helpdesk_categorie (id)');
        $this->addSql('ALTER TABLE helpdesk_categorie ADD CONSTRAINT FK_C912A228ED5CA9E6 FOREIGN KEY (service_id) REFERENCES structure_service (id)');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('La migration initiale ne s’annule pas.');
    }
}
