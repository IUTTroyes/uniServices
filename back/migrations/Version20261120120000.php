<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261120120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée les tables du parc automobile, des réservations et du suivi des véhicules.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE parcauto_journal_vehicule (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(25) NOT NULL, date DATETIME(6) NOT NULL, libelle VARCHAR(255) NOT NULL, kilometrage INT DEFAULT NULL, montant_centimes INT DEFAULT NULL, volume_millilitres INT DEFAULT NULL, usure_dixiemes_mm INT DEFAULT NULL, vehicule_id INT NOT NULL, auteur_id INT NOT NULL, prestataire_id INT DEFAULT NULL, carte_id INT DEFAULT NULL, INDEX idx_parcauto_journal (vehicule_id, type, date), INDEX IDX_728EB7B44A4A3511 (vehicule_id), INDEX IDX_728EB7B460BB6FE6 (auteur_id), INDEX IDX_728EB7B4BE3DB2B7 (prestataire_id), INDEX IDX_728EB7B4C9C7CEB6 (carte_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE parcauto_echeance_entretien (id INT AUTO_INCREMENT NOT NULL, effectue_le DATETIME(6) DEFAULT NULL, kilometrage_controle INT DEFAULT NULL, intervalle_mois INT DEFAULT NULL, intervalle_km INT DEFAULT NULL, type VARCHAR(30) NOT NULL, libelle VARCHAR(150) NOT NULL, echeance DATETIME(6) NOT NULL, echeance_km INT DEFAULT NULL, vehicule_id INT NOT NULL, UNIQUE INDEX uniq_parcauto_vehicule_entretien (vehicule_id, type, libelle), INDEX IDX_FBDA2FE54A4A3511 (vehicule_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE parcauto_vehicule (id INT AUTO_INCREMENT NOT NULL, mis_en_service_le DATETIME(6) DEFAULT NULL, reforme_prevue_le DATETIME(6) DEFAULT NULL, reforme_km INT DEFAULT NULL, achat_centimes INT NOT NULL, consommation_reference INT DEFAULT NULL, immatriculation VARCHAR(24) NOT NULL, libelle VARCHAR(150) NOT NULL, site VARCHAR(120) NOT NULL, energie VARCHAR(30) NOT NULL, kilometrage INT NOT NULL, actif TINYINT NOT NULL, UNIQUE INDEX UNIQ_8469C777BE73422E (immatriculation), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE parcauto_reservation (id INT AUTO_INCREMENT NOT NULL, statut VARCHAR(16) NOT NULL, depart_le DATETIME(6) DEFAULT NULL, retour_le DATETIME(6) DEFAULT NULL, kilometrage_depart INT DEFAULT NULL, kilometrage_retour INT DEFAULT NULL, nom_conducteur VARCHAR(200) NOT NULL, debut DATETIME(6) NOT NULL, fin DATETIME(6) NOT NULL, ordre_mission VARCHAR(120) NOT NULL, ordre_verifie TINYINT NOT NULL, vehicule_id INT NOT NULL, conducteur_id INT NOT NULL, INDEX idx_parcauto_reservation_planning (vehicule_id, statut, debut, fin), INDEX IDX_8FA85F504A4A3511 (vehicule_id), INDEX IDX_8FA85F50F16F4AC6 (conducteur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE parcauto_equipement (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(20) NOT NULL, reference VARCHAR(120) NOT NULL, libelle VARCHAR(150) NOT NULL, coordonnees VARCHAR(255) NOT NULL, actif TINYINT NOT NULL, vehicule_id INT DEFAULT NULL, UNIQUE INDEX uniq_parcauto_equipement (type, reference), INDEX IDX_FA450064A4A3511 (vehicule_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE parcauto_journal_vehicule ADD CONSTRAINT FK_728EB7B44A4A3511 FOREIGN KEY (vehicule_id) REFERENCES parcauto_vehicule (id)');
        $this->addSql('ALTER TABLE parcauto_journal_vehicule ADD CONSTRAINT FK_728EB7B460BB6FE6 FOREIGN KEY (auteur_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE parcauto_journal_vehicule ADD CONSTRAINT FK_728EB7B4BE3DB2B7 FOREIGN KEY (prestataire_id) REFERENCES parcauto_equipement (id)');
        $this->addSql('ALTER TABLE parcauto_journal_vehicule ADD CONSTRAINT FK_728EB7B4C9C7CEB6 FOREIGN KEY (carte_id) REFERENCES parcauto_equipement (id)');
        $this->addSql('ALTER TABLE parcauto_echeance_entretien ADD CONSTRAINT FK_FBDA2FE54A4A3511 FOREIGN KEY (vehicule_id) REFERENCES parcauto_vehicule (id)');
        $this->addSql('ALTER TABLE parcauto_reservation ADD CONSTRAINT FK_8FA85F504A4A3511 FOREIGN KEY (vehicule_id) REFERENCES parcauto_vehicule (id)');
        $this->addSql('ALTER TABLE parcauto_reservation ADD CONSTRAINT FK_8FA85F50F16F4AC6 FOREIGN KEY (conducteur_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE parcauto_equipement ADD CONSTRAINT FK_FA450064A4A3511 FOREIGN KEY (vehicule_id) REFERENCES parcauto_vehicule (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE parcauto_journal_vehicule');
        $this->addSql('DROP TABLE parcauto_echeance_entretien');
        $this->addSql('DROP TABLE parcauto_reservation');
        $this->addSql('DROP TABLE parcauto_equipement');
        $this->addSql('DROP TABLE parcauto_vehicule');
    }
}
