<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241126105923 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE structure_annee_universitaire (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(30) NOT NULL, annee INT NOT NULL, commentaire LONGTEXT DEFAULT NULL, actif TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE structure_annee_universitaire_structure_pn (structure_annee_universitaire_id INT NOT NULL, structure_pn_id INT NOT NULL, INDEX IDX_5F04608F951E7E6F (structure_annee_universitaire_id), INDEX IDX_5F04608FA8D8D056 (structure_pn_id), PRIMARY KEY(structure_annee_universitaire_id, structure_pn_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE structure_scolarite (id INT AUTO_INCREMENT NOT NULL, semestre_id INT NOT NULL, etudiant_id INT NOT NULL, structure_annee_universitaire_id INT NOT NULL, ordre INT NOT NULL, proposition VARCHAR(10) DEFAULT NULL, moyenne DOUBLE PRECISION DEFAULT NULL, nb_absences INT NOT NULL, commentaire LONGTEXT DEFAULT NULL, public TINYINT(1) NOT NULL, moyennes_matiere JSON DEFAULT NULL, moyennes_ue JSON DEFAULT NULL, actif TINYINT(1) NOT NULL, INDEX IDX_B13AFD875577AFDB (semestre_id), INDEX IDX_B13AFD87DDEAB1A3 (etudiant_id), INDEX IDX_B13AFD87951E7E6F (structure_annee_universitaire_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE structure_annee_universitaire_structure_pn ADD CONSTRAINT FK_5F04608F951E7E6F FOREIGN KEY (structure_annee_universitaire_id) REFERENCES structure_annee_universitaire (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE structure_annee_universitaire_structure_pn ADD CONSTRAINT FK_5F04608FA8D8D056 FOREIGN KEY (structure_pn_id) REFERENCES structure_pn (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE structure_scolarite ADD CONSTRAINT FK_B13AFD875577AFDB FOREIGN KEY (semestre_id) REFERENCES structure_semestre (id)');
        $this->addSql('ALTER TABLE structure_scolarite ADD CONSTRAINT FK_B13AFD87DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES structure_etudiant (id)');
        $this->addSql('ALTER TABLE structure_scolarite ADD CONSTRAINT FK_B13AFD87951E7E6F FOREIGN KEY (structure_annee_universitaire_id) REFERENCES structure_annee_universitaire (id)');
        $this->addSql('ALTER TABLE structure_personnel ADD structure_annee_universitaire_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE structure_personnel ADD CONSTRAINT FK_30E45EF2951E7E6F FOREIGN KEY (structure_annee_universitaire_id) REFERENCES structure_annee_universitaire (id)');
        $this->addSql('CREATE INDEX IDX_30E45EF2951E7E6F ON structure_personnel (structure_annee_universitaire_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE structure_personnel DROP FOREIGN KEY FK_30E45EF2951E7E6F');
        $this->addSql('ALTER TABLE structure_annee_universitaire_structure_pn DROP FOREIGN KEY FK_5F04608F951E7E6F');
        $this->addSql('ALTER TABLE structure_annee_universitaire_structure_pn DROP FOREIGN KEY FK_5F04608FA8D8D056');
        $this->addSql('ALTER TABLE structure_scolarite DROP FOREIGN KEY FK_B13AFD875577AFDB');
        $this->addSql('ALTER TABLE structure_scolarite DROP FOREIGN KEY FK_B13AFD87DDEAB1A3');
        $this->addSql('ALTER TABLE structure_scolarite DROP FOREIGN KEY FK_B13AFD87951E7E6F');
        $this->addSql('DROP TABLE structure_annee_universitaire');
        $this->addSql('DROP TABLE structure_annee_universitaire_structure_pn');
        $this->addSql('DROP TABLE structure_scolarite');
        $this->addSql('DROP INDEX IDX_30E45EF2951E7E6F ON structure_personnel');
        $this->addSql('ALTER TABLE structure_personnel DROP structure_annee_universitaire_id');
    }
}
