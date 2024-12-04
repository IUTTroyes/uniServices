<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241204110239 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE apc_apprentissage_critique ADD apc_niveau_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE apc_apprentissage_critique ADD CONSTRAINT FK_A99B947A9445617E FOREIGN KEY (apc_niveau_id) REFERENCES apc_niveau (id)');
        $this->addSql('CREATE INDEX IDX_A99B947A9445617E ON apc_apprentissage_critique (apc_niveau_id)');
        $this->addSql('ALTER TABLE apc_competence DROP FOREIGN KEY FK_B949FC0F805DB139');
        $this->addSql('DROP INDEX IDX_B949FC0F805DB139 ON apc_competence');
        $this->addSql('ALTER TABLE apc_competence CHANGE referentiel_id apc_referentiel_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE apc_competence ADD CONSTRAINT FK_B949FC0F9048A9AB FOREIGN KEY (apc_referentiel_id) REFERENCES apc_referentiel (id)');
        $this->addSql('CREATE INDEX IDX_B949FC0F9048A9AB ON apc_competence (apc_referentiel_id)');
        $this->addSql('ALTER TABLE apc_parcours ADD apc_referentiel_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE apc_parcours ADD CONSTRAINT FK_5DA884A59048A9AB FOREIGN KEY (apc_referentiel_id) REFERENCES apc_referentiel (id)');
        $this->addSql('CREATE INDEX IDX_5DA884A59048A9AB ON apc_parcours (apc_referentiel_id)');
        $this->addSql('ALTER TABLE apc_referentiel ADD annee_univ_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE apc_referentiel ADD CONSTRAINT FK_E744CC817D34CA3C FOREIGN KEY (annee_univ_id) REFERENCES structure_annee_universitaire (id)');
        $this->addSql('CREATE INDEX IDX_E744CC817D34CA3C ON apc_referentiel (annee_univ_id)');
        $this->addSql('ALTER TABLE scol_enseignement ADD code_apogee VARCHAR(25) DEFAULT NULL');
        $this->addSql('ALTER TABLE structure_groupe CHANGE code_apogee code_apogee VARCHAR(25) DEFAULT NULL');
        $this->addSql('ALTER TABLE structure_pn ADD apc_referentiel_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE structure_pn ADD CONSTRAINT FK_5EE1408D9048A9AB FOREIGN KEY (apc_referentiel_id) REFERENCES apc_referentiel (id)');
        $this->addSql('CREATE INDEX IDX_5EE1408D9048A9AB ON structure_pn (apc_referentiel_id)');
        $this->addSql('ALTER TABLE structure_ue ADD semestre_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE structure_ue ADD CONSTRAINT FK_B4446D405577AFDB FOREIGN KEY (semestre_id) REFERENCES structure_semestre (id)');
        $this->addSql('CREATE INDEX IDX_B4446D405577AFDB ON structure_ue (semestre_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE apc_apprentissage_critique DROP FOREIGN KEY FK_A99B947A9445617E');
        $this->addSql('DROP INDEX IDX_A99B947A9445617E ON apc_apprentissage_critique');
        $this->addSql('ALTER TABLE apc_apprentissage_critique DROP apc_niveau_id');
        $this->addSql('ALTER TABLE apc_competence DROP FOREIGN KEY FK_B949FC0F9048A9AB');
        $this->addSql('DROP INDEX IDX_B949FC0F9048A9AB ON apc_competence');
        $this->addSql('ALTER TABLE apc_competence CHANGE apc_referentiel_id referentiel_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE apc_competence ADD CONSTRAINT FK_B949FC0F805DB139 FOREIGN KEY (referentiel_id) REFERENCES apc_referentiel (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_B949FC0F805DB139 ON apc_competence (referentiel_id)');
        $this->addSql('ALTER TABLE apc_parcours DROP FOREIGN KEY FK_5DA884A59048A9AB');
        $this->addSql('DROP INDEX IDX_5DA884A59048A9AB ON apc_parcours');
        $this->addSql('ALTER TABLE apc_parcours DROP apc_referentiel_id');
        $this->addSql('ALTER TABLE apc_referentiel DROP FOREIGN KEY FK_E744CC817D34CA3C');
        $this->addSql('DROP INDEX IDX_E744CC817D34CA3C ON apc_referentiel');
        $this->addSql('ALTER TABLE apc_referentiel DROP annee_univ_id');
        $this->addSql('ALTER TABLE scol_enseignement DROP code_apogee');
        $this->addSql('ALTER TABLE structure_groupe CHANGE code_apogee code_apogee VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE structure_pn DROP FOREIGN KEY FK_5EE1408D9048A9AB');
        $this->addSql('DROP INDEX IDX_5EE1408D9048A9AB ON structure_pn');
        $this->addSql('ALTER TABLE structure_pn DROP apc_referentiel_id');
        $this->addSql('ALTER TABLE structure_ue DROP FOREIGN KEY FK_B4446D405577AFDB');
        $this->addSql('DROP INDEX IDX_B4446D405577AFDB ON structure_ue');
        $this->addSql('ALTER TABLE structure_ue DROP semestre_id');
    }
}
