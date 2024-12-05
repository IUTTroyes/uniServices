<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241205084731 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE etudiant_note (id INT AUTO_INCREMENT NOT NULL, evaluation_id INT DEFAULT NULL, scolarite_id INT DEFAULT NULL, note DOUBLE PRECISION NOT NULL, commentaire LONGTEXT DEFAULT NULL, absence_justifiee TINYINT(1) NOT NULL, historique JSON DEFAULT NULL, uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_9F9C5D58456C5646 (evaluation_id), INDEX IDX_9F9C5D58AA6B2AB6 (scolarite_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE etudiant_note ADD CONSTRAINT FK_9F9C5D58456C5646 FOREIGN KEY (evaluation_id) REFERENCES scol_evaluation (id)');
        $this->addSql('ALTER TABLE etudiant_note ADD CONSTRAINT FK_9F9C5D58AA6B2AB6 FOREIGN KEY (scolarite_id) REFERENCES etudiant_scolarite (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE etudiant_note DROP FOREIGN KEY FK_9F9C5D58456C5646');
        $this->addSql('ALTER TABLE etudiant_note DROP FOREIGN KEY FK_9F9C5D58AA6B2AB6');
        $this->addSql('DROP TABLE etudiant_note');
    }
}
