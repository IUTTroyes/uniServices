<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241203181725 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE scol_enseignement (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, scol_enseignement_id INT DEFAULT NULL, libelle VARCHAR(255) NOT NULL, libelle_court VARCHAR(25) DEFAULT NULL, pre_requis LONGTEXT DEFAULT NULL, objectif LONGTEXT DEFAULT NULL, description LONGTEXT DEFAULT NULL, mots_cles LONGTEXT DEFAULT NULL, code_matiere VARCHAR(20) DEFAULT NULL, suspendu TINYINT(1) NOT NULL, heures_ppn JSON NOT NULL, heures_formation JSON NOT NULL, nb_notes INT NOT NULL, code_element VARCHAR(20) DEFAULT NULL, mutualisee TINYINT(1) NOT NULL, livrables LONGTEXT DEFAULT NULL, exemple LONGTEXT DEFAULT NULL, bonification TINYINT(1) NOT NULL, INDEX IDX_2AF1A2727ACA70 (parent_id), INDEX IDX_2AF1A26AA15514 (scol_enseignement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE scol_enseignement_structure_ue (scol_enseignement_id INT NOT NULL, structure_ue_id INT NOT NULL, INDEX IDX_264CA1686AA15514 (scol_enseignement_id), INDEX IDX_264CA168B739D827 (structure_ue_id), PRIMARY KEY(scol_enseignement_id, structure_ue_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE scol_enseignement_apc_apprentissage_critique (scol_enseignement_id INT NOT NULL, apc_apprentissage_critique_id INT NOT NULL, INDEX IDX_578CC1B86AA15514 (scol_enseignement_id), INDEX IDX_578CC1B84DD72DCD (apc_apprentissage_critique_id), PRIMARY KEY(scol_enseignement_id, apc_apprentissage_critique_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE scol_enseignement ADD CONSTRAINT FK_2AF1A2727ACA70 FOREIGN KEY (parent_id) REFERENCES scol_enseignement (id)');
        $this->addSql('ALTER TABLE scol_enseignement ADD CONSTRAINT FK_2AF1A26AA15514 FOREIGN KEY (scol_enseignement_id) REFERENCES scol_enseignement (id)');
        $this->addSql('ALTER TABLE scol_enseignement_structure_ue ADD CONSTRAINT FK_264CA1686AA15514 FOREIGN KEY (scol_enseignement_id) REFERENCES scol_enseignement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE scol_enseignement_structure_ue ADD CONSTRAINT FK_264CA168B739D827 FOREIGN KEY (structure_ue_id) REFERENCES structure_ue (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE scol_enseignement_apc_apprentissage_critique ADD CONSTRAINT FK_578CC1B86AA15514 FOREIGN KEY (scol_enseignement_id) REFERENCES scol_enseignement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE scol_enseignement_apc_apprentissage_critique ADD CONSTRAINT FK_578CC1B84DD72DCD FOREIGN KEY (apc_apprentissage_critique_id) REFERENCES apc_apprentissage_critique (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE scol_enseignement DROP FOREIGN KEY FK_2AF1A2727ACA70');
        $this->addSql('ALTER TABLE scol_enseignement DROP FOREIGN KEY FK_2AF1A26AA15514');
        $this->addSql('ALTER TABLE scol_enseignement_structure_ue DROP FOREIGN KEY FK_264CA1686AA15514');
        $this->addSql('ALTER TABLE scol_enseignement_structure_ue DROP FOREIGN KEY FK_264CA168B739D827');
        $this->addSql('ALTER TABLE scol_enseignement_apc_apprentissage_critique DROP FOREIGN KEY FK_578CC1B86AA15514');
        $this->addSql('ALTER TABLE scol_enseignement_apc_apprentissage_critique DROP FOREIGN KEY FK_578CC1B84DD72DCD');
        $this->addSql('DROP TABLE scol_enseignement');
        $this->addSql('DROP TABLE scol_enseignement_structure_ue');
        $this->addSql('DROP TABLE scol_enseignement_apc_apprentissage_critique');
    }
}
