<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261108194559 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Questionnaires : diffusion ciblée des résultats, retours des enseignants, année universitaire.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE questionnaire_diffusion (id INT AUTO_INCREMENT NOT NULL, origine VARCHAR(20) NOT NULL, notifie_le DATETIME(6) DEFAULT NULL, section_instance_id INT NOT NULL, personnel_id INT NOT NULL, UNIQUE INDEX uniq_diffusion (section_instance_id, personnel_id), INDEX IDX_CAAF85CF2CA00D62 (section_instance_id), INDEX IDX_CAAF85CF1C109075 (personnel_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE questionnaire_retour (id INT AUTO_INCREMENT NOT NULL, commentaire LONGTEXT DEFAULT NULL, plan_action LONGTEXT DEFAULT NULL, modifie_le DATETIME(6) NOT NULL, section_instance_id INT NOT NULL, personnel_id INT NOT NULL, UNIQUE INDEX uniq_retour (section_instance_id, personnel_id), INDEX IDX_74C9DC182CA00D62 (section_instance_id), INDEX IDX_74C9DC181C109075 (personnel_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE questionnaire_diffusion ADD CONSTRAINT FK_CAAF85CF2CA00D62 FOREIGN KEY (section_instance_id) REFERENCES questionnaire_section_instance (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE questionnaire_diffusion ADD CONSTRAINT FK_CAAF85CF1C109075 FOREIGN KEY (personnel_id) REFERENCES personnel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE questionnaire_retour ADD CONSTRAINT FK_74C9DC182CA00D62 FOREIGN KEY (section_instance_id) REFERENCES questionnaire_section_instance (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE questionnaire_retour ADD CONSTRAINT FK_74C9DC181C109075 FOREIGN KEY (personnel_id) REFERENCES personnel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE questionnaire_destinataire_synthese DROP FOREIGN KEY `FK_366051121C109075`');
        $this->addSql('ALTER TABLE questionnaire_destinataire_synthese DROP FOREIGN KEY `FK_36605112CE07E8FF`');
        $this->addSql('DROP TABLE questionnaire_destinataire_synthese');
        $this->addSql('ALTER TABLE questionnaire ADD cloture_le DATETIME(6) DEFAULT NULL, ADD diffusion_initialisee TINYINT DEFAULT 0 NOT NULL, ADD diffusion_validee_le DATETIME(6) DEFAULT NULL, ADD resultats_publies_le DATETIME(6) DEFAULT NULL, ADD annee_universitaire_id INT DEFAULT NULL, ADD diffusion_validee_par_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE questionnaire ADD CONSTRAINT FK_7A64DAF544BFD58 FOREIGN KEY (annee_universitaire_id) REFERENCES structure_annee_universitaire (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE questionnaire ADD CONSTRAINT FK_7A64DAFCE902CA9 FOREIGN KEY (diffusion_validee_par_id) REFERENCES personnel (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_7A64DAF544BFD58 ON questionnaire (annee_universitaire_id)');
        $this->addSql('CREATE INDEX IDX_7A64DAFCE902CA9 ON questionnaire (diffusion_validee_par_id)');
        $this->addSql('ALTER TABLE questionnaire_section_instance ADD enseignement_id INT DEFAULT NULL, ADD personnel_cible_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE questionnaire_section_instance ADD CONSTRAINT FK_8F705045ABEC3B20 FOREIGN KEY (enseignement_id) REFERENCES scol_enseignement (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE questionnaire_section_instance ADD CONSTRAINT FK_8F70504521F454C FOREIGN KEY (personnel_cible_id) REFERENCES personnel (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_8F705045ABEC3B20 ON questionnaire_section_instance (enseignement_id)');
        $this->addSql('CREATE INDEX IDX_8F70504521F454C ON questionnaire_section_instance (personnel_cible_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE questionnaire_destinataire_synthese (questionnaire_id INT NOT NULL, personnel_id INT NOT NULL, INDEX IDX_366051121C109075 (personnel_id), INDEX IDX_36605112CE07E8FF (questionnaire_id), PRIMARY KEY (questionnaire_id, personnel_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE questionnaire_destinataire_synthese ADD CONSTRAINT `FK_366051121C109075` FOREIGN KEY (personnel_id) REFERENCES personnel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE questionnaire_destinataire_synthese ADD CONSTRAINT `FK_36605112CE07E8FF` FOREIGN KEY (questionnaire_id) REFERENCES questionnaire (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE questionnaire_diffusion DROP FOREIGN KEY FK_CAAF85CF2CA00D62');
        $this->addSql('ALTER TABLE questionnaire_diffusion DROP FOREIGN KEY FK_CAAF85CF1C109075');
        $this->addSql('ALTER TABLE questionnaire_retour DROP FOREIGN KEY FK_74C9DC182CA00D62');
        $this->addSql('ALTER TABLE questionnaire_retour DROP FOREIGN KEY FK_74C9DC181C109075');
        $this->addSql('DROP TABLE questionnaire_diffusion');
        $this->addSql('DROP TABLE questionnaire_retour');
        $this->addSql('ALTER TABLE questionnaire DROP FOREIGN KEY FK_7A64DAF544BFD58');
        $this->addSql('ALTER TABLE questionnaire DROP FOREIGN KEY FK_7A64DAFCE902CA9');
        $this->addSql('DROP INDEX IDX_7A64DAF544BFD58 ON questionnaire');
        $this->addSql('DROP INDEX IDX_7A64DAFCE902CA9 ON questionnaire');
        $this->addSql('ALTER TABLE questionnaire DROP cloture_le, DROP diffusion_initialisee, DROP diffusion_validee_le, DROP resultats_publies_le, DROP annee_universitaire_id, DROP diffusion_validee_par_id');
        $this->addSql('ALTER TABLE questionnaire_section_instance DROP FOREIGN KEY FK_8F705045ABEC3B20');
        $this->addSql('ALTER TABLE questionnaire_section_instance DROP FOREIGN KEY FK_8F70504521F454C');
        $this->addSql('DROP INDEX IDX_8F705045ABEC3B20 ON questionnaire_section_instance');
        $this->addSql('DROP INDEX IDX_8F70504521F454C ON questionnaire_section_instance');
        $this->addSql('ALTER TABLE questionnaire_section_instance DROP enseignement_id, DROP personnel_cible_id');
    }
}
