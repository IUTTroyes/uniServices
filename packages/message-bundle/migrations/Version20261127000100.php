<?php

declare(strict_types=1);

namespace MessageBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Schéma initial (MySQL) : crée les tables sur une base vide, ne touche pas à une base existante. */
final class Version20261127000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Schéma initial : MessageBundle';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        // Base existante : la migration est enregistrée comme exécutée, sans aucune requête.
        if ($schema->hasTable('message')) {
            $this->write('Tables déjà présentes : rien à créer.');

            return;
        }

        $this->addSql('CREATE TABLE message_piece_jointe (id INT AUTO_INCREMENT NOT NULL, nom_original VARCHAR(255) NOT NULL, fichier VARCHAR(255) NOT NULL, taille INT NOT NULL, mime_type VARCHAR(150) DEFAULT NULL, created_at DATETIME(6) NOT NULL, message_id INT NOT NULL, INDEX IDX_6F502432537A1329 (message_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE message_destinataire (id INT AUTO_INCREMENT NOT NULL, is_lu TINYINT NOT NULL, date_lecture DATETIME(6) DEFAULT NULL, is_archive TINYINT NOT NULL, is_copie TINYINT DEFAULT 0 NOT NULL, created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, message_id INT NOT NULL, etudiant_id INT DEFAULT NULL, personnel_id INT DEFAULT NULL, INDEX idx_message_dest_user (etudiant_id, personnel_id, is_lu), INDEX IDX_3AA8CA74537A1329 (message_id), INDEX IDX_3AA8CA74DDEAB1A3 (etudiant_id), INDEX IDX_3AA8CA741C109075 (personnel_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE message (id INT AUTO_INCREMENT NOT NULL, sujet VARCHAR(255) NOT NULL, contenu LONGTEXT NOT NULL, type_cible VARCHAR(50) NOT NULL, cible_libelle VARCHAR(255) DEFAULT NULL, prioritaire TINYINT NOT NULL, copie_email TINYINT NOT NULL, created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, expediteur_id INT NOT NULL, departement_id INT DEFAULT NULL, annee_universitaire_id INT DEFAULT NULL, INDEX IDX_B6BD307F10335F61 (expediteur_id), INDEX IDX_B6BD307FCCF9E01E (departement_id), INDEX IDX_B6BD307F544BFD58 (annee_universitaire_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE message_piece_jointe ADD CONSTRAINT FK_6F502432537A1329 FOREIGN KEY (message_id) REFERENCES message (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message_destinataire ADD CONSTRAINT FK_3AA8CA74537A1329 FOREIGN KEY (message_id) REFERENCES message (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message_destinataire ADD CONSTRAINT FK_3AA8CA74DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES etudiant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message_destinataire ADD CONSTRAINT FK_3AA8CA741C109075 FOREIGN KEY (personnel_id) REFERENCES personnel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F10335F61 FOREIGN KEY (expediteur_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FCCF9E01E FOREIGN KEY (departement_id) REFERENCES structure_departement (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F544BFD58 FOREIGN KEY (annee_universitaire_id) REFERENCES structure_annee_universitaire (id)');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('La migration initiale ne s’annule pas.');
    }
}
