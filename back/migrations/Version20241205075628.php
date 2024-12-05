<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241205075628 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE scol_evaluation (id INT AUTO_INCREMENT NOT NULL, annee_univ_id INT DEFAULT NULL, semestre_id INT DEFAULT NULL, parent_id INT DEFAULT NULL, enseignement_id INT NOT NULL, libelle VARCHAR(255) NOT NULL, commentaire VARCHAR(255) DEFAULT NULL, coeff DOUBLE PRECISION DEFAULT NULL, date DATETIME(6) DEFAULT NULL, visible TINYINT(1) NOT NULL, modifiable TINYINT(1) NOT NULL, INDEX IDX_2F7AD3C37D34CA3C (annee_univ_id), INDEX IDX_2F7AD3C35577AFDB (semestre_id), INDEX IDX_2F7AD3C3727ACA70 (parent_id), INDEX IDX_2F7AD3C3ABEC3B20 (enseignement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE scol_evaluation_personnel (scol_evaluation_id INT NOT NULL, personnel_id INT NOT NULL, INDEX IDX_51475EB0EF6DE3F8 (scol_evaluation_id), INDEX IDX_51475EB01C109075 (personnel_id), PRIMARY KEY(scol_evaluation_id, personnel_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE scol_evaluation ADD CONSTRAINT FK_2F7AD3C37D34CA3C FOREIGN KEY (annee_univ_id) REFERENCES structure_annee_universitaire (id)');
        $this->addSql('ALTER TABLE scol_evaluation ADD CONSTRAINT FK_2F7AD3C35577AFDB FOREIGN KEY (semestre_id) REFERENCES structure_semestre (id)');
        $this->addSql('ALTER TABLE scol_evaluation ADD CONSTRAINT FK_2F7AD3C3727ACA70 FOREIGN KEY (parent_id) REFERENCES scol_evaluation (id)');
        $this->addSql('ALTER TABLE scol_evaluation ADD CONSTRAINT FK_2F7AD3C3ABEC3B20 FOREIGN KEY (enseignement_id) REFERENCES scol_enseignement (id)');
        $this->addSql('ALTER TABLE scol_evaluation_personnel ADD CONSTRAINT FK_51475EB0EF6DE3F8 FOREIGN KEY (scol_evaluation_id) REFERENCES scol_evaluation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE scol_evaluation_personnel ADD CONSTRAINT FK_51475EB01C109075 FOREIGN KEY (personnel_id) REFERENCES personnel (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE scol_evaluation DROP FOREIGN KEY FK_2F7AD3C37D34CA3C');
        $this->addSql('ALTER TABLE scol_evaluation DROP FOREIGN KEY FK_2F7AD3C35577AFDB');
        $this->addSql('ALTER TABLE scol_evaluation DROP FOREIGN KEY FK_2F7AD3C3727ACA70');
        $this->addSql('ALTER TABLE scol_evaluation DROP FOREIGN KEY FK_2F7AD3C3ABEC3B20');
        $this->addSql('ALTER TABLE scol_evaluation_personnel DROP FOREIGN KEY FK_51475EB0EF6DE3F8');
        $this->addSql('ALTER TABLE scol_evaluation_personnel DROP FOREIGN KEY FK_51475EB01C109075');
        $this->addSql('DROP TABLE scol_evaluation');
        $this->addSql('DROP TABLE scol_evaluation_personnel');
    }
}
