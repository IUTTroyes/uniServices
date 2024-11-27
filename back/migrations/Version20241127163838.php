<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241127163838 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE etudiant (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(75) NOT NULL, mail_univ VARCHAR(255) NOT NULL, password VARCHAR(255) DEFAULT NULL, roles JSON NOT NULL, prenom VARCHAR(75) NOT NULL, nom VARCHAR(75) NOT NULL, photo_name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE personnel (id INT AUTO_INCREMENT NOT NULL, structure_annee_universitaire_id INT DEFAULT NULL, username VARCHAR(75) NOT NULL, mail_univ VARCHAR(255) NOT NULL, password VARCHAR(255) DEFAULT NULL, roles JSON NOT NULL, prenom VARCHAR(75) NOT NULL, nom VARCHAR(75) NOT NULL, photo_name VARCHAR(255) NOT NULL, INDEX IDX_A6BCF3DE951E7E6F (structure_annee_universitaire_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE structure_annee (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(255) NOT NULL, code_etape VARCHAR(20) DEFAULT NULL, code_version VARCHAR(10) DEFAULT NULL, ordre INT NOT NULL, libelle_long VARCHAR(255) DEFAULT NULL, actif TINYINT(1) NOT NULL, couleur VARCHAR(30) DEFAULT NULL, opt JSON NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE structure_annee_universitaire (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(30) NOT NULL, annee INT NOT NULL, commentaire LONGTEXT DEFAULT NULL, actif TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE structure_annee_universitaire_structure_pn (structure_annee_universitaire_id INT NOT NULL, structure_pn_id INT NOT NULL, INDEX IDX_5F04608F951E7E6F (structure_annee_universitaire_id), INDEX IDX_5F04608FA8D8D056 (structure_pn_id), PRIMARY KEY(structure_annee_universitaire_id, structure_pn_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE structure_departement (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(255) NOT NULL, logo_name VARCHAR(255) DEFAULT NULL, tel_contact VARCHAR(16) DEFAULT NULL, couleur VARCHAR(16) DEFAULT NULL, site_web VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, actif TINYINT(1) NOT NULL, opt JSON NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE structure_departement_personnel (id INT AUTO_INCREMENT NOT NULL, personnel_id INT NOT NULL, departement_id INT DEFAULT NULL, roles JSON NOT NULL, defaut TINYINT(1) NOT NULL, INDEX IDX_153CA20D1C109075 (personnel_id), INDEX IDX_153CA20DCCF9E01E (departement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE structure_diplome (id INT AUTO_INCREMENT NOT NULL, responsable_diplome_id INT DEFAULT NULL, assistant_diplome_id INT DEFAULT NULL, parent_id INT DEFAULT NULL, departement_id INT DEFAULT NULL, libelle VARCHAR(255) NOT NULL, volume_horaire INT NOT NULL, code_celcat_departement INT NOT NULL, sigle VARCHAR(40) DEFAULT NULL, actif TINYINT(1) NOT NULL, logo_partenaire VARCHAR(255) DEFAULT NULL, opt JSON NOT NULL, INDEX IDX_30EE3D7FD2D1AAE2 (responsable_diplome_id), INDEX IDX_30EE3D7F39A24FD8 (assistant_diplome_id), INDEX IDX_30EE3D7F727ACA70 (parent_id), INDEX IDX_30EE3D7FCCF9E01E (departement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE structure_groupe (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, libelle VARCHAR(255) NOT NULL, code_apogee VARCHAR(50) NOT NULL, type VARCHAR(10) NOT NULL, ordre INT DEFAULT NULL, INDEX IDX_1D006B0B727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE structure_groupe_etudiant (structure_groupe_id INT NOT NULL, etudiant_id INT NOT NULL, INDEX IDX_EF2254F6EC1D98A0 (structure_groupe_id), INDEX IDX_EF2254F6DDEAB1A3 (etudiant_id), PRIMARY KEY(structure_groupe_id, etudiant_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE structure_groupe_structure_semestre (structure_groupe_id INT NOT NULL, structure_semestre_id INT NOT NULL, INDEX IDX_DF2D36EDEC1D98A0 (structure_groupe_id), INDEX IDX_DF2D36EDC4218D78 (structure_semestre_id), PRIMARY KEY(structure_groupe_id, structure_semestre_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE structure_pn (id INT AUTO_INCREMENT NOT NULL, diplome_id INT DEFAULT NULL, structure_annee_id INT DEFAULT NULL, libelle VARCHAR(255) NOT NULL, annee_publication INT NOT NULL, INDEX IDX_5EE1408D26F859E2 (diplome_id), INDEX IDX_5EE1408D53B67BA (structure_annee_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE structure_scolarite (id INT AUTO_INCREMENT NOT NULL, semestre_id INT NOT NULL, etudiant_id INT NOT NULL, structure_annee_universitaire_id INT NOT NULL, ordre INT NOT NULL, proposition VARCHAR(10) DEFAULT NULL, moyenne DOUBLE PRECISION DEFAULT NULL, nb_absences INT NOT NULL, commentaire LONGTEXT DEFAULT NULL, public TINYINT(1) NOT NULL, moyennes_matiere JSON DEFAULT NULL, moyennes_ue JSON DEFAULT NULL, actif TINYINT(1) NOT NULL, INDEX IDX_B13AFD875577AFDB (semestre_id), INDEX IDX_B13AFD87DDEAB1A3 (etudiant_id), INDEX IDX_B13AFD87951E7E6F (structure_annee_universitaire_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE structure_semestre (id INT AUTO_INCREMENT NOT NULL, annee_id INT DEFAULT NULL, libelle VARCHAR(255) NOT NULL, ordre_annee INT NOT NULL, ordre_lmd INT NOT NULL, actif TINYINT(1) NOT NULL, nb_groupes_cm INT NOT NULL, nb_groupes_td INT NOT NULL, nb_groupes_tp VARCHAR(255) NOT NULL, code_element VARCHAR(20) DEFAULT NULL, opt JSON NOT NULL, INDEX IDX_206D2DF6543EC5F0 (annee_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE personnel ADD CONSTRAINT FK_A6BCF3DE951E7E6F FOREIGN KEY (structure_annee_universitaire_id) REFERENCES structure_annee_universitaire (id)');
        $this->addSql('ALTER TABLE structure_annee_universitaire_structure_pn ADD CONSTRAINT FK_5F04608F951E7E6F FOREIGN KEY (structure_annee_universitaire_id) REFERENCES structure_annee_universitaire (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE structure_annee_universitaire_structure_pn ADD CONSTRAINT FK_5F04608FA8D8D056 FOREIGN KEY (structure_pn_id) REFERENCES structure_pn (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE structure_departement_personnel ADD CONSTRAINT FK_153CA20D1C109075 FOREIGN KEY (personnel_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE structure_departement_personnel ADD CONSTRAINT FK_153CA20DCCF9E01E FOREIGN KEY (departement_id) REFERENCES structure_departement (id)');
        $this->addSql('ALTER TABLE structure_diplome ADD CONSTRAINT FK_30EE3D7FD2D1AAE2 FOREIGN KEY (responsable_diplome_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE structure_diplome ADD CONSTRAINT FK_30EE3D7F39A24FD8 FOREIGN KEY (assistant_diplome_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE structure_diplome ADD CONSTRAINT FK_30EE3D7F727ACA70 FOREIGN KEY (parent_id) REFERENCES structure_diplome (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE structure_diplome ADD CONSTRAINT FK_30EE3D7FCCF9E01E FOREIGN KEY (departement_id) REFERENCES structure_departement (id)');
        $this->addSql('ALTER TABLE structure_groupe ADD CONSTRAINT FK_1D006B0B727ACA70 FOREIGN KEY (parent_id) REFERENCES structure_groupe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE structure_groupe_etudiant ADD CONSTRAINT FK_EF2254F6EC1D98A0 FOREIGN KEY (structure_groupe_id) REFERENCES structure_groupe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE structure_groupe_etudiant ADD CONSTRAINT FK_EF2254F6DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES etudiant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE structure_groupe_structure_semestre ADD CONSTRAINT FK_DF2D36EDEC1D98A0 FOREIGN KEY (structure_groupe_id) REFERENCES structure_groupe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE structure_groupe_structure_semestre ADD CONSTRAINT FK_DF2D36EDC4218D78 FOREIGN KEY (structure_semestre_id) REFERENCES structure_semestre (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE structure_pn ADD CONSTRAINT FK_5EE1408D26F859E2 FOREIGN KEY (diplome_id) REFERENCES structure_diplome (id)');
        $this->addSql('ALTER TABLE structure_pn ADD CONSTRAINT FK_5EE1408D53B67BA FOREIGN KEY (structure_annee_id) REFERENCES structure_annee (id)');
        $this->addSql('ALTER TABLE structure_scolarite ADD CONSTRAINT FK_B13AFD875577AFDB FOREIGN KEY (semestre_id) REFERENCES structure_semestre (id)');
        $this->addSql('ALTER TABLE structure_scolarite ADD CONSTRAINT FK_B13AFD87DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES etudiant (id)');
        $this->addSql('ALTER TABLE structure_scolarite ADD CONSTRAINT FK_B13AFD87951E7E6F FOREIGN KEY (structure_annee_universitaire_id) REFERENCES structure_annee_universitaire (id)');
        $this->addSql('ALTER TABLE structure_semestre ADD CONSTRAINT FK_206D2DF6543EC5F0 FOREIGN KEY (annee_id) REFERENCES structure_annee (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE personnel DROP FOREIGN KEY FK_A6BCF3DE951E7E6F');
        $this->addSql('ALTER TABLE structure_annee_universitaire_structure_pn DROP FOREIGN KEY FK_5F04608F951E7E6F');
        $this->addSql('ALTER TABLE structure_annee_universitaire_structure_pn DROP FOREIGN KEY FK_5F04608FA8D8D056');
        $this->addSql('ALTER TABLE structure_departement_personnel DROP FOREIGN KEY FK_153CA20D1C109075');
        $this->addSql('ALTER TABLE structure_departement_personnel DROP FOREIGN KEY FK_153CA20DCCF9E01E');
        $this->addSql('ALTER TABLE structure_diplome DROP FOREIGN KEY FK_30EE3D7FD2D1AAE2');
        $this->addSql('ALTER TABLE structure_diplome DROP FOREIGN KEY FK_30EE3D7F39A24FD8');
        $this->addSql('ALTER TABLE structure_diplome DROP FOREIGN KEY FK_30EE3D7F727ACA70');
        $this->addSql('ALTER TABLE structure_diplome DROP FOREIGN KEY FK_30EE3D7FCCF9E01E');
        $this->addSql('ALTER TABLE structure_groupe DROP FOREIGN KEY FK_1D006B0B727ACA70');
        $this->addSql('ALTER TABLE structure_groupe_etudiant DROP FOREIGN KEY FK_EF2254F6EC1D98A0');
        $this->addSql('ALTER TABLE structure_groupe_etudiant DROP FOREIGN KEY FK_EF2254F6DDEAB1A3');
        $this->addSql('ALTER TABLE structure_groupe_structure_semestre DROP FOREIGN KEY FK_DF2D36EDEC1D98A0');
        $this->addSql('ALTER TABLE structure_groupe_structure_semestre DROP FOREIGN KEY FK_DF2D36EDC4218D78');
        $this->addSql('ALTER TABLE structure_pn DROP FOREIGN KEY FK_5EE1408D26F859E2');
        $this->addSql('ALTER TABLE structure_pn DROP FOREIGN KEY FK_5EE1408D53B67BA');
        $this->addSql('ALTER TABLE structure_scolarite DROP FOREIGN KEY FK_B13AFD875577AFDB');
        $this->addSql('ALTER TABLE structure_scolarite DROP FOREIGN KEY FK_B13AFD87DDEAB1A3');
        $this->addSql('ALTER TABLE structure_scolarite DROP FOREIGN KEY FK_B13AFD87951E7E6F');
        $this->addSql('ALTER TABLE structure_semestre DROP FOREIGN KEY FK_206D2DF6543EC5F0');
        $this->addSql('DROP TABLE etudiant');
        $this->addSql('DROP TABLE personnel');
        $this->addSql('DROP TABLE structure_annee');
        $this->addSql('DROP TABLE structure_annee_universitaire');
        $this->addSql('DROP TABLE structure_annee_universitaire_structure_pn');
        $this->addSql('DROP TABLE structure_departement');
        $this->addSql('DROP TABLE structure_departement_personnel');
        $this->addSql('DROP TABLE structure_diplome');
        $this->addSql('DROP TABLE structure_groupe');
        $this->addSql('DROP TABLE structure_groupe_etudiant');
        $this->addSql('DROP TABLE structure_groupe_structure_semestre');
        $this->addSql('DROP TABLE structure_pn');
        $this->addSql('DROP TABLE structure_scolarite');
        $this->addSql('DROP TABLE structure_semestre');
    }
}
