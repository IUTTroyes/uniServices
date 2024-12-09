<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241209074300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE scol_enseignement_ue (id INT AUTO_INCREMENT NOT NULL, enseignement_id INT DEFAULT NULL, ue_id INT DEFAULT NULL, ects DOUBLE PRECISION NOT NULL, coefficient DOUBLE PRECISION NOT NULL, INDEX IDX_FBD138DFABEC3B20 (enseignement_id), INDEX IDX_FBD138DF62E883B1 (ue_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE scol_enseignement_ue ADD CONSTRAINT FK_FBD138DFABEC3B20 FOREIGN KEY (enseignement_id) REFERENCES scol_enseignement (id)');
        $this->addSql('ALTER TABLE scol_enseignement_ue ADD CONSTRAINT FK_FBD138DF62E883B1 FOREIGN KEY (ue_id) REFERENCES structure_ue (id)');
        $this->addSql('ALTER TABLE scol_enseignement_structure_ue DROP FOREIGN KEY FK_264CA1686AA15514');
        $this->addSql('ALTER TABLE scol_enseignement_structure_ue DROP FOREIGN KEY FK_264CA168B739D827');
        $this->addSql('DROP TABLE scol_enseignement_structure_ue');
        $this->addSql('ALTER TABLE apc_competence ADD old_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE apc_niveau DROP FOREIGN KEY FK_5CE8A823543EC5F0');
        $this->addSql('DROP INDEX IDX_5CE8A823543EC5F0 ON apc_niveau');
        $this->addSql('ALTER TABLE apc_niveau DROP annee_id');
        $this->addSql('ALTER TABLE apc_parcours ADD old_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE apc_referentiel ADD type_diplome_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE apc_referentiel ADD CONSTRAINT FK_E744CC813BFB8FC7 FOREIGN KEY (type_diplome_id) REFERENCES structure_type_diplome (id)');
        $this->addSql('CREATE INDEX IDX_E744CC813BFB8FC7 ON apc_referentiel (type_diplome_id)');
        $this->addSql('ALTER TABLE etudiant ADD old_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE personnel ADD old_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE scol_enseignement DROP FOREIGN KEY FK_2AF1A26AA15514');
        $this->addSql('DROP INDEX IDX_2AF1A26AA15514 ON scol_enseignement');
        $this->addSql('ALTER TABLE scol_enseignement CHANGE type type VARCHAR(255) NOT NULL, CHANGE scol_enseignement_id old_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE structure_annee ADD apc_niveau_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE structure_annee ADD CONSTRAINT FK_B439E599445617E FOREIGN KEY (apc_niveau_id) REFERENCES apc_niveau (id)');
        $this->addSql('CREATE INDEX IDX_B439E599445617E ON structure_annee (apc_niveau_id)');
        $this->addSql('ALTER TABLE structure_annee_universitaire ADD old_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE structure_departement ADD old_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE structure_semestre ADD old_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE structure_ue ADD old_id INT DEFAULT NULL, CHANGE nb_ects nb_ects DOUBLE PRECISION NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE scol_enseignement_structure_ue (scol_enseignement_id INT NOT NULL, structure_ue_id INT NOT NULL, INDEX IDX_264CA1686AA15514 (scol_enseignement_id), INDEX IDX_264CA168B739D827 (structure_ue_id), PRIMARY KEY(scol_enseignement_id, structure_ue_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE scol_enseignement_structure_ue ADD CONSTRAINT FK_264CA1686AA15514 FOREIGN KEY (scol_enseignement_id) REFERENCES scol_enseignement (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE scol_enseignement_structure_ue ADD CONSTRAINT FK_264CA168B739D827 FOREIGN KEY (structure_ue_id) REFERENCES structure_ue (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE scol_enseignement_ue DROP FOREIGN KEY FK_FBD138DFABEC3B20');
        $this->addSql('ALTER TABLE scol_enseignement_ue DROP FOREIGN KEY FK_FBD138DF62E883B1');
        $this->addSql('DROP TABLE scol_enseignement_ue');
        $this->addSql('ALTER TABLE apc_competence DROP old_id');
        $this->addSql('ALTER TABLE apc_niveau ADD annee_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE apc_niveau ADD CONSTRAINT FK_5CE8A823543EC5F0 FOREIGN KEY (annee_id) REFERENCES structure_annee (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_5CE8A823543EC5F0 ON apc_niveau (annee_id)');
        $this->addSql('ALTER TABLE apc_parcours DROP old_id');
        $this->addSql('ALTER TABLE apc_referentiel DROP FOREIGN KEY FK_E744CC813BFB8FC7');
        $this->addSql('DROP INDEX IDX_E744CC813BFB8FC7 ON apc_referentiel');
        $this->addSql('ALTER TABLE apc_referentiel DROP type_diplome_id');
        $this->addSql('ALTER TABLE etudiant DROP old_id');
        $this->addSql('ALTER TABLE personnel DROP old_id');
        $this->addSql('ALTER TABLE scol_enseignement CHANGE type type SMALLINT NOT NULL, CHANGE old_id scol_enseignement_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE scol_enseignement ADD CONSTRAINT FK_2AF1A26AA15514 FOREIGN KEY (scol_enseignement_id) REFERENCES scol_enseignement (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_2AF1A26AA15514 ON scol_enseignement (scol_enseignement_id)');
        $this->addSql('ALTER TABLE structure_annee DROP FOREIGN KEY FK_B439E599445617E');
        $this->addSql('DROP INDEX IDX_B439E599445617E ON structure_annee');
        $this->addSql('ALTER TABLE structure_annee DROP apc_niveau_id');
        $this->addSql('ALTER TABLE structure_annee_universitaire DROP old_id');
        $this->addSql('ALTER TABLE structure_departement DROP old_id');
        $this->addSql('ALTER TABLE structure_semestre DROP old_id');
        $this->addSql('ALTER TABLE structure_ue DROP old_id, CHANGE nb_ects nb_ects INT NOT NULL');
    }
}
