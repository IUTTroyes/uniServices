<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261126151055 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Mise à jour du schéma : date_debut_semaine_formation sur annee_universitaire, versions résultats sous-commission sur semestre, nettoyage relations';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE structure_annee_universitaire ADD date_debut_semaine_formation DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE structure_semestre ADD resultats_version INT DEFAULT 1 NOT NULL, ADD resultats_generation INT DEFAULT 1 NOT NULL, DROP actif, DROP nb_groupes_cm, DROP nb_groupes_td, DROP nb_groupes_tp');
        $this->addSql('ALTER TABLE personnel DROP FOREIGN KEY `FK_A6BCF3DE544BFD58`');
        $this->addSql('DROP INDEX IDX_A6BCF3DE544BFD58 ON personnel');
        $this->addSql('ALTER TABLE personnel DROP annee_universitaire_id');
        $this->addSql('ALTER TABLE structure_groupe DROP FOREIGN KEY `FK_1D006B0B6E38C0DB`');
        $this->addSql('DROP INDEX IDX_1D006B0B6E38C0DB ON structure_groupe');
        $this->addSql('ALTER TABLE structure_groupe DROP parcours_id');
        $this->addSql('ALTER TABLE unifolio_trace CHANGE date_realisation date_realisation DATETIME(6) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE personnel ADD annee_universitaire_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE personnel ADD CONSTRAINT `FK_A6BCF3DE544BFD58` FOREIGN KEY (annee_universitaire_id) REFERENCES structure_annee_universitaire (id)');
        $this->addSql('CREATE INDEX IDX_A6BCF3DE544BFD58 ON personnel (annee_universitaire_id)');
        $this->addSql('ALTER TABLE structure_annee_universitaire DROP date_debut_semaine_formation');
        $this->addSql('ALTER TABLE structure_groupe ADD parcours_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE structure_groupe ADD CONSTRAINT `FK_1D006B0B6E38C0DB` FOREIGN KEY (parcours_id) REFERENCES apc_parcours (id)');
        $this->addSql('CREATE INDEX IDX_1D006B0B6E38C0DB ON structure_groupe (parcours_id)');
        $this->addSql('ALTER TABLE structure_semestre ADD actif TINYINT NOT NULL, ADD nb_groupes_td INT NOT NULL, ADD nb_groupes_tp INT NOT NULL, DROP resultats_version, CHANGE resultats_generation nb_groupes_cm INT NOT NULL');
        $this->addSql('ALTER TABLE unifolio_trace CHANGE date_realisation date_realisation DATE DEFAULT NULL');
    }
}
