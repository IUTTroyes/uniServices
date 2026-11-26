<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261110164834 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du type_evenement sur edt_event, table intranet_avancement_enseignement et suppression structure_diplome_annee_universitaire';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE intranet_avancement_enseignement (id INT AUTO_INCREMENT NOT NULL, auteur_libelle VARCHAR(255) NOT NULL, repere LONGTEXT NOT NULL, prochaine_etape LONGTEXT NOT NULL, sequence_snapshot JSON DEFAULT NULL, etat_sequence VARCHAR(20) DEFAULT NULL, created_at DATETIME(6) NOT NULL, enseignement_id INT NOT NULL, groupe_id INT NOT NULL, annee_universitaire_id INT NOT NULL, auteur_id INT DEFAULT NULL, INDEX idx_avancement_perimetre (enseignement_id, annee_universitaire_id, groupe_id, id), INDEX IDX_F3FC587ABEC3B20 (enseignement_id), INDEX IDX_F3FC5877A45358C (groupe_id), INDEX IDX_F3FC587544BFD58 (annee_universitaire_id), INDEX IDX_F3FC58760BB6FE6 (auteur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE intranet_avancement_enseignement ADD CONSTRAINT FK_F3FC587ABEC3B20 FOREIGN KEY (enseignement_id) REFERENCES scol_enseignement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE intranet_avancement_enseignement ADD CONSTRAINT FK_F3FC5877A45358C FOREIGN KEY (groupe_id) REFERENCES structure_groupe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE intranet_avancement_enseignement ADD CONSTRAINT FK_F3FC587544BFD58 FOREIGN KEY (annee_universitaire_id) REFERENCES structure_annee_universitaire (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE intranet_avancement_enseignement ADD CONSTRAINT FK_F3FC58760BB6FE6 FOREIGN KEY (auteur_id) REFERENCES personnel (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE structure_diplome_annee_universitaire DROP FOREIGN KEY `FK_195F796B14B66DAC`');
        $this->addSql('ALTER TABLE structure_diplome_annee_universitaire DROP FOREIGN KEY `FK_195F796B951E7E6F`');
        $this->addSql('DROP TABLE structure_diplome_annee_universitaire');
        $this->addSql('ALTER TABLE edt_event ADD type_evenement VARCHAR(20) DEFAULT \'autre\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE structure_diplome_annee_universitaire (structure_diplome_id INT NOT NULL, structure_annee_universitaire_id INT NOT NULL, INDEX IDX_195F796B14B66DAC (structure_diplome_id), INDEX IDX_195F796B951E7E6F (structure_annee_universitaire_id), PRIMARY KEY (structure_diplome_id, structure_annee_universitaire_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE structure_diplome_annee_universitaire ADD CONSTRAINT `FK_195F796B14B66DAC` FOREIGN KEY (structure_diplome_id) REFERENCES structure_diplome (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE structure_diplome_annee_universitaire ADD CONSTRAINT `FK_195F796B951E7E6F` FOREIGN KEY (structure_annee_universitaire_id) REFERENCES structure_annee_universitaire (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE intranet_avancement_enseignement DROP FOREIGN KEY FK_F3FC587ABEC3B20');
        $this->addSql('ALTER TABLE intranet_avancement_enseignement DROP FOREIGN KEY FK_F3FC5877A45358C');
        $this->addSql('ALTER TABLE intranet_avancement_enseignement DROP FOREIGN KEY FK_F3FC587544BFD58');
        $this->addSql('ALTER TABLE intranet_avancement_enseignement DROP FOREIGN KEY FK_F3FC58760BB6FE6');
        $this->addSql('DROP TABLE intranet_avancement_enseignement');
        $this->addSql('ALTER TABLE edt_event DROP type_evenement');
    }
}
