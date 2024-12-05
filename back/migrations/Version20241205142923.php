<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241205142923 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE etudiant_absence_justificatif (id INT AUTO_INCREMENT NOT NULL, date DATETIME(6) NOT NULL, heure_debut DATETIME(6) NOT NULL, heure_fin DATETIME(6) NOT NULL, motif LONGTEXT DEFAULT NULL, etat SMALLINT NOT NULL, fichier VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE etudiant_absence ADD etudiant_absence_justificatif_id INT DEFAULT NULL, ADD uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE etudiant_absence ADD CONSTRAINT FK_A2F5AE4DA4ACC5ED FOREIGN KEY (etudiant_absence_justificatif_id) REFERENCES etudiant_absence_justificatif (id)');
        $this->addSql('CREATE INDEX IDX_A2F5AE4DA4ACC5ED ON etudiant_absence (etudiant_absence_justificatif_id)');
        $this->addSql('ALTER TABLE etudiant_scolarite ADD uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE scol_edt_event ADD uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', ADD key_edu_sign VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE scol_evaluation ADD uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE etudiant_absence DROP FOREIGN KEY FK_A2F5AE4DA4ACC5ED');
        $this->addSql('DROP TABLE etudiant_absence_justificatif');
        $this->addSql('DROP INDEX IDX_A2F5AE4DA4ACC5ED ON etudiant_absence');
        $this->addSql('ALTER TABLE etudiant_absence DROP etudiant_absence_justificatif_id, DROP uuid');
        $this->addSql('ALTER TABLE etudiant_scolarite DROP uuid');
        $this->addSql('ALTER TABLE scol_edt_event DROP uuid, DROP key_edu_sign');
        $this->addSql('ALTER TABLE scol_evaluation DROP uuid');
    }
}
