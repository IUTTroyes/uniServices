<?php

declare(strict_types=1);

namespace QuestionnaireBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Schéma initial (MySQL) : crée les tables sur une base vide, ne touche pas à une base existante. */
final class Version20261127000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Schéma initial : QuestionnaireBundle';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        // Base existante : la migration est enregistrée comme exécutée, sans aucune requête.
        if ($schema->hasTable('questionnaire')) {
            $this->write('Tables déjà présentes : rien à créer.');

            return;
        }

        $this->addSql('CREATE TABLE questionnaire_diffusion (id INT AUTO_INCREMENT NOT NULL, origine VARCHAR(20) NOT NULL, notifie_le DATETIME(6) DEFAULT NULL, section_instance_id INT NOT NULL, personnel_id INT NOT NULL, UNIQUE INDEX uniq_diffusion (section_instance_id, personnel_id), INDEX IDX_CAAF85CF2CA00D62 (section_instance_id), INDEX IDX_CAAF85CF1C109075 (personnel_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE questionnaire_question (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, help VARCHAR(255) DEFAULT NULL, type_question VARCHAR(255) DEFAULT NULL, required TINYINT NOT NULL, sort_order INT NOT NULL, choices JSON DEFAULT NULL, uuid BINARY(16) NOT NULL, conditional_rules JSON DEFAULT NULL, origine VARCHAR(36) DEFAULT NULL, opt JSON NOT NULL, section_id INT DEFAULT NULL, INDEX idx_question_section_ordre (section_id, sort_order), INDEX IDX_28CC40C3D823E37A (section_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE questionnaire_section (id INT AUTO_INCREMENT NOT NULL, uuid BINARY(16) NOT NULL, title VARCHAR(255) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, type_section VARCHAR(255) DEFAULT NULL, sort_order INT NOT NULL, opt JSON NOT NULL, questionnaire_id INT DEFAULT NULL, INDEX idx_section_questionnaire_ordre (questionnaire_id, sort_order), INDEX IDX_72EF54E8CE07E8FF (questionnaire_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE questionnaire (uuid BINARY(16) NOT NULL, id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, opening_date DATETIME(6) DEFAULT NULL, closing_date DATETIME(6) DEFAULT NULL, start_text LONGTEXT DEFAULT NULL, end_text LONGTEXT DEFAULT NULL, status VARCHAR(50) NOT NULL, published_at DATETIME(6) DEFAULT NULL, estimated_time INT DEFAULT NULL, completion_notified TINYINT DEFAULT 0 NOT NULL, est_modele TINYINT DEFAULT 0 NOT NULL, cloture_le DATETIME(6) DEFAULT NULL, diffusion_initialisee TINYINT DEFAULT 0 NOT NULL, diffusion_validee_le DATETIME(6) DEFAULT NULL, purge_le DATETIME(6) DEFAULT NULL, resultats_archives JSON DEFAULT NULL, resultats_publies_le DATETIME(6) DEFAULT NULL, completion_notified_at DATETIME(6) DEFAULT NULL, created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, opt JSON NOT NULL, departement_id INT DEFAULT NULL, author_id INT DEFAULT NULL, modele_origine_id INT DEFAULT NULL, annee_universitaire_id INT DEFAULT NULL, diffusion_validee_par_id INT DEFAULT NULL, INDEX IDX_7A64DAFCCF9E01E (departement_id), INDEX IDX_7A64DAFF675F31B (author_id), INDEX IDX_7A64DAFF3D08E7F (modele_origine_id), INDEX IDX_7A64DAF544BFD58 (annee_universitaire_id), INDEX IDX_7A64DAFCE902CA9 (diffusion_validee_par_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE questionnaire_retour (id INT AUTO_INCREMENT NOT NULL, commentaire LONGTEXT DEFAULT NULL, plan_action LONGTEXT DEFAULT NULL, modifie_le DATETIME(6) NOT NULL, section_instance_id INT NOT NULL, personnel_id INT NOT NULL, UNIQUE INDEX uniq_retour (section_instance_id, personnel_id), INDEX IDX_74C9DC182CA00D62 (section_instance_id), INDEX IDX_74C9DC181C109075 (personnel_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE questionnaire_invitation (id INT AUTO_INCREMENT NOT NULL, token VARCHAR(64) NOT NULL, email VARCHAR(255) DEFAULT NULL, status VARCHAR(255) DEFAULT NULL, created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, started_at DATETIME(6) DEFAULT NULL, submitted_at DATETIME(6) DEFAULT NULL, reminded_at DATETIME(6) DEFAULT NULL, reminders_count INT DEFAULT 0 NOT NULL, reminders_history JSON DEFAULT NULL, questionnaire_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_6ECA13B5F37A13B (token), INDEX idx_invitation_token (token), INDEX idx_invitation_questionnaire (questionnaire_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE questionnaire_answer (id INT AUTO_INCREMENT NOT NULL, value JSON DEFAULT NULL, created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, invitation_id INT DEFAULT NULL, section_id INT DEFAULT NULL, question_id INT DEFAULT NULL, INDEX idx_answer_invitation (invitation_id), UNIQUE INDEX uniq_answer (invitation_id, section_id, question_id), INDEX IDX_437B451CD823E37A (section_id), INDEX IDX_437B451C1E27F6BF (question_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE questionnaire_section_instance (id INT AUTO_INCREMENT NOT NULL, sort_order INT NOT NULL, title_snapshot VARCHAR(255) DEFAULT NULL, repeat_section_item_type VARCHAR(255) DEFAULT NULL, repeat_section_item_id VARCHAR(255) DEFAULT NULL, questionnaire_id INT DEFAULT NULL, section_id INT DEFAULT NULL, enseignement_id INT DEFAULT NULL, personnel_cible_id INT DEFAULT NULL, INDEX idx_pubsec_questionnaire_order (questionnaire_id, sort_order), UNIQUE INDEX uniq_pubsec (questionnaire_id, section_id, repeat_section_item_type, repeat_section_item_id), INDEX IDX_8F705045CE07E8FF (questionnaire_id), INDEX IDX_8F705045D823E37A (section_id), INDEX IDX_8F705045ABEC3B20 (enseignement_id), INDEX IDX_8F70504521F454C (personnel_cible_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE questionnaire_diffusion ADD CONSTRAINT FK_CAAF85CF2CA00D62 FOREIGN KEY (section_instance_id) REFERENCES questionnaire_section_instance (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE questionnaire_diffusion ADD CONSTRAINT FK_CAAF85CF1C109075 FOREIGN KEY (personnel_id) REFERENCES personnel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE questionnaire_question ADD CONSTRAINT FK_28CC40C3D823E37A FOREIGN KEY (section_id) REFERENCES questionnaire_section (id)');
        $this->addSql('ALTER TABLE questionnaire_section ADD CONSTRAINT FK_72EF54E8CE07E8FF FOREIGN KEY (questionnaire_id) REFERENCES questionnaire (id)');
        $this->addSql('ALTER TABLE questionnaire ADD CONSTRAINT FK_7A64DAFCCF9E01E FOREIGN KEY (departement_id) REFERENCES structure_departement (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE questionnaire ADD CONSTRAINT FK_7A64DAFF675F31B FOREIGN KEY (author_id) REFERENCES personnel (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE questionnaire ADD CONSTRAINT FK_7A64DAFF3D08E7F FOREIGN KEY (modele_origine_id) REFERENCES questionnaire (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE questionnaire ADD CONSTRAINT FK_7A64DAF544BFD58 FOREIGN KEY (annee_universitaire_id) REFERENCES structure_annee_universitaire (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE questionnaire ADD CONSTRAINT FK_7A64DAFCE902CA9 FOREIGN KEY (diffusion_validee_par_id) REFERENCES personnel (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE questionnaire_retour ADD CONSTRAINT FK_74C9DC182CA00D62 FOREIGN KEY (section_instance_id) REFERENCES questionnaire_section_instance (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE questionnaire_retour ADD CONSTRAINT FK_74C9DC181C109075 FOREIGN KEY (personnel_id) REFERENCES personnel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE questionnaire_invitation ADD CONSTRAINT FK_6ECA13BCE07E8FF FOREIGN KEY (questionnaire_id) REFERENCES questionnaire (id)');
        $this->addSql('ALTER TABLE questionnaire_answer ADD CONSTRAINT FK_437B451CA35D7AF0 FOREIGN KEY (invitation_id) REFERENCES questionnaire_invitation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE questionnaire_answer ADD CONSTRAINT FK_437B451CD823E37A FOREIGN KEY (section_id) REFERENCES questionnaire_section_instance (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE questionnaire_answer ADD CONSTRAINT FK_437B451C1E27F6BF FOREIGN KEY (question_id) REFERENCES questionnaire_question (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE questionnaire_section_instance ADD CONSTRAINT FK_8F705045CE07E8FF FOREIGN KEY (questionnaire_id) REFERENCES questionnaire (id)');
        $this->addSql('ALTER TABLE questionnaire_section_instance ADD CONSTRAINT FK_8F705045D823E37A FOREIGN KEY (section_id) REFERENCES questionnaire_section (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE questionnaire_section_instance ADD CONSTRAINT FK_8F705045ABEC3B20 FOREIGN KEY (enseignement_id) REFERENCES scol_enseignement (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE questionnaire_section_instance ADD CONSTRAINT FK_8F70504521F454C FOREIGN KEY (personnel_cible_id) REFERENCES personnel (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('La migration initiale ne s’annule pas.');
    }
}
