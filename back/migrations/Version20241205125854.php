<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241205125854 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE scol_edt_event (id INT AUTO_INCREMENT NOT NULL, personnel_id INT DEFAULT NULL, enseignement_id INT DEFAULT NULL, groupe_id INT DEFAULT NULL, annee_universitaire_id INT DEFAULT NULL, semestre_id INT DEFAULT NULL, semaine_formation INT DEFAULT NULL, jour INT DEFAULT NULL, debut DATETIME(6) DEFAULT NULL, fin DATETIME(6) DEFAULT NULL, salle VARCHAR(25) NOT NULL, code_salle VARCHAR(25) DEFAULT NULL, code_personnel VARCHAR(20) DEFAULT NULL, lib_personnel VARCHAR(255) DEFAULT NULL, code_module VARCHAR(20) DEFAULT NULL, lib_module VARCHAR(50) DEFAULT NULL, type_matiere VARCHAR(15) DEFAULT NULL, code_groupe VARCHAR(30) DEFAULT NULL, lib_groupe VARCHAR(255) DEFAULT NULL, couleur VARCHAR(20) DEFAULT NULL, celcat_id INT DEFAULT NULL, type VARCHAR(20) DEFAULT NULL, departement_code_celcat INT DEFAULT NULL, updated_event DATETIME(6) DEFAULT NULL, evaluation TINYINT(1) NOT NULL, INDEX IDX_E6BDAA801C109075 (personnel_id), INDEX IDX_E6BDAA80ABEC3B20 (enseignement_id), INDEX IDX_E6BDAA807A45358C (groupe_id), INDEX IDX_E6BDAA80544BFD58 (annee_universitaire_id), INDEX IDX_E6BDAA805577AFDB (semestre_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE scol_edt_event ADD CONSTRAINT FK_E6BDAA801C109075 FOREIGN KEY (personnel_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE scol_edt_event ADD CONSTRAINT FK_E6BDAA80ABEC3B20 FOREIGN KEY (enseignement_id) REFERENCES scol_enseignement (id)');
        $this->addSql('ALTER TABLE scol_edt_event ADD CONSTRAINT FK_E6BDAA807A45358C FOREIGN KEY (groupe_id) REFERENCES structure_groupe (id)');
        $this->addSql('ALTER TABLE scol_edt_event ADD CONSTRAINT FK_E6BDAA80544BFD58 FOREIGN KEY (annee_universitaire_id) REFERENCES structure_annee_universitaire (id)');
        $this->addSql('ALTER TABLE scol_edt_event ADD CONSTRAINT FK_E6BDAA805577AFDB FOREIGN KEY (semestre_id) REFERENCES structure_semestre (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE scol_edt_event DROP FOREIGN KEY FK_E6BDAA801C109075');
        $this->addSql('ALTER TABLE scol_edt_event DROP FOREIGN KEY FK_E6BDAA80ABEC3B20');
        $this->addSql('ALTER TABLE scol_edt_event DROP FOREIGN KEY FK_E6BDAA807A45358C');
        $this->addSql('ALTER TABLE scol_edt_event DROP FOREIGN KEY FK_E6BDAA80544BFD58');
        $this->addSql('ALTER TABLE scol_edt_event DROP FOREIGN KEY FK_E6BDAA805577AFDB');
        $this->addSql('DROP TABLE scol_edt_event');
    }
}
