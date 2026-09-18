<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260918170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add updated_at to existing timestamped entities migrated to TimestampableInterface';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE document_category ADD updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addSql('UPDATE document_category SET updated_at = created_at WHERE updated_at IS NULL');
        $this->addSql("ALTER TABLE document_category MODIFY updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'");

        $this->addSql("ALTER TABLE questionnaire_invitation ADD updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addSql('UPDATE questionnaire_invitation SET updated_at = created_at WHERE updated_at IS NULL');
        $this->addSql("ALTER TABLE questionnaire_invitation MODIFY updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'");
    }

        $this->addSql('ALTER TABLE etudiant RENAME COLUMN created TO created_at, RENAME COLUMN updated TO updated_at');
        $this->addSql('ALTER TABLE personnel RENAME COLUMN created TO created_at, RENAME COLUMN updated TO updated_at');
        $this->addSql('ALTER TABLE edt_event RENAME COLUMN created TO created_at, RENAME COLUMN updated TO updated_at');
        $this->addSql('ALTER TABLE email_template RENAME COLUMN created TO created_at, RENAME COLUMN updated TO updated_at');
        $this->addSql('ALTER TABLE departement_actualite RENAME COLUMN created TO created_at, RENAME COLUMN updated TO updated_at');
        $this->addSql('ALTER TABLE etudiant_note RENAME COLUMN created TO created_at, RENAME COLUMN updated TO updated_at');
        $this->addSql('ALTER TABLE structure_ue RENAME COLUMN created TO created_at, RENAME COLUMN updated TO updated_at');
        $this->addSql('ALTER TABLE structure_annee RENAME COLUMN created TO created_at, RENAME COLUMN updated TO updated_at');
        $this->addSql('ALTER TABLE structure_diplome RENAME COLUMN created TO created_at, RENAME COLUMN updated TO updated_at');
        $this->addSql('ALTER TABLE structure_semestre RENAME COLUMN created TO created_at, RENAME COLUMN updated TO updated_at');
        $this->addSql('ALTER TABLE structure_departement RENAME COLUMN created TO created_at, RENAME COLUMN updated TO updated_at');
        $this->addSql('ALTER TABLE contact RENAME COLUMN created TO created_at, RENAME COLUMN updated TO updated_at');
        $this->addSql('ALTER TABLE entreprise RENAME COLUMN created TO created_at, RENAME COLUMN updated TO updated_at');
        $this->addSql('ALTER TABLE scol_evaluation_rattrapage RENAME COLUMN created TO created_at, RENAME COLUMN updated TO updated_at');
        $this->addSql('ALTER TABLE helpdesk_ticket RENAME COLUMN created TO created_at, RENAME COLUMN updated TO updated_at');
        $this->addSql('ALTER TABLE helpdesk_message RENAME COLUMN created TO created_at, RENAME COLUMN updated TO updated_at');
        $this->addSql('ALTER TABLE stage_avenant RENAME COLUMN created TO created_at, RENAME COLUMN updated TO updated_at');
        $this->addSql('ALTER TABLE structure_annee_universitaire RENAME COLUMN created TO created_at, RENAME COLUMN updated TO updated_at');
        $this->addSql('ALTER TABLE stage_etudiant RENAME COLUMN created TO created_at, RENAME COLUMN updated TO updated_at');
        $this->addSql('ALTER TABLE stage_soutenance RENAME COLUMN created TO created_at, RENAME COLUMN updated TO updated_at');
        $this->addSql('ALTER TABLE etudiant_absence RENAME COLUMN created TO created_at, RENAME COLUMN updated TO updated_at');
        $this->addSql('ALTER TABLE questionnaire RENAME COLUMN created TO created_at, RENAME COLUMN updated TO updated_at');
        $this->addSql('ALTER TABLE questionnaire_answer RENAME COLUMN created TO created_at, RENAME COLUMN updated TO updated_at');

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE etudiant RENAME COLUMN created_at TO created, RENAME COLUMN updated_at TO updated');
        $this->addSql('ALTER TABLE personnel RENAME COLUMN created_at TO created, RENAME COLUMN updated_at TO updated');
        $this->addSql('ALTER TABLE edt_event RENAME COLUMN created_at TO created, RENAME COLUMN updated_at TO updated');
        $this->addSql('ALTER TABLE email_template RENAME COLUMN created_at TO created, RENAME COLUMN updated_at TO updated');
        $this->addSql('ALTER TABLE departement_actualite RENAME COLUMN created_at TO created, RENAME COLUMN updated_at TO updated');
        $this->addSql('ALTER TABLE etudiant_note RENAME COLUMN created_at TO created, RENAME COLUMN updated_at TO updated');
        $this->addSql('ALTER TABLE structure_ue RENAME COLUMN created_at TO created, RENAME COLUMN updated_at TO updated');
        $this->addSql('ALTER TABLE structure_annee RENAME COLUMN created_at TO created, RENAME COLUMN updated_at TO updated');
        $this->addSql('ALTER TABLE structure_diplome RENAME COLUMN created_at TO created, RENAME COLUMN updated_at TO updated');
        $this->addSql('ALTER TABLE structure_semestre RENAME COLUMN created_at TO created, RENAME COLUMN updated_at TO updated');
        $this->addSql('ALTER TABLE structure_departement RENAME COLUMN created_at TO created, RENAME COLUMN updated_at TO updated');
        $this->addSql('ALTER TABLE contact RENAME COLUMN created_at TO created, RENAME COLUMN updated_at TO updated');
        $this->addSql('ALTER TABLE entreprise RENAME COLUMN created_at TO created, RENAME COLUMN updated_at TO updated');
        $this->addSql('ALTER TABLE scol_evaluation_rattrapage RENAME COLUMN created_at TO created, RENAME COLUMN updated_at TO updated');
        $this->addSql('ALTER TABLE helpdesk_ticket RENAME COLUMN created_at TO created, RENAME COLUMN updated_at TO updated');
        $this->addSql('ALTER TABLE helpdesk_message RENAME COLUMN created_at TO created, RENAME COLUMN updated_at TO updated');
        $this->addSql('ALTER TABLE stage_avenant RENAME COLUMN created_at TO created, RENAME COLUMN updated_at TO updated');
        $this->addSql('ALTER TABLE structure_annee_universitaire RENAME COLUMN created_at TO created, RENAME COLUMN updated_at TO updated');
        $this->addSql('ALTER TABLE stage_etudiant RENAME COLUMN created_at TO created, RENAME COLUMN updated_at TO updated');
        $this->addSql('ALTER TABLE stage_soutenance RENAME COLUMN created_at TO created, RENAME COLUMN updated_at TO updated');
        $this->addSql('ALTER TABLE etudiant_absence RENAME COLUMN created_at TO created, RENAME COLUMN updated_at TO updated');
        $this->addSql('ALTER TABLE questionnaire RENAME COLUMN created_at TO created, RENAME COLUMN updated_at TO updated');
        $this->addSql('ALTER TABLE questionnaire_answer RENAME COLUMN created_at TO created, RENAME COLUMN updated_at TO updated');
        $this->addSql('ALTER TABLE document_category DROP updated_at');
        $this->addSql('ALTER TABLE questionnaire_invitation DROP updated_at');
    }
}
