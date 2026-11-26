<?php

declare(strict_types=1);

namespace ParcautoBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Schéma initial (MySQL) : crée les tables sur une base vide, ne touche pas à une base existante. */
final class Version20261127000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Schéma initial : ParcautoBundle';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        // Base existante : la migration est enregistrée comme exécutée, sans aucune requête.
        if ($schema->hasTable('parcauto_echeance_entretien')) {
            $this->write('Tables déjà présentes : rien à créer.');

            return;
        }

        $this->addSql('CREATE TABLE parcauto_journal_vehicule (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(25) NOT NULL, date DATETIME(6) NOT NULL, libelle VARCHAR(255) NOT NULL, kilometrage INT DEFAULT NULL, montant_centimes INT DEFAULT NULL, volume_millilitres INT DEFAULT NULL, usure_dixiemes_mm INT DEFAULT NULL, numero_facture VARCHAR(120) DEFAULT NULL, vehicule_id INT NOT NULL, auteur_id INT NOT NULL, prestataire_id INT DEFAULT NULL, carte_id INT DEFAULT NULL, reservation_id INT DEFAULT NULL, pass_id INT DEFAULT NULL, INDEX idx_parcauto_journal (vehicule_id, type, date), INDEX IDX_728EB7B44A4A3511 (vehicule_id), INDEX IDX_728EB7B460BB6FE6 (auteur_id), INDEX IDX_728EB7B4BE3DB2B7 (prestataire_id), INDEX IDX_728EB7B4C9C7CEB6 (carte_id), INDEX IDX_728EB7B4B83297E7 (reservation_id), INDEX IDX_728EB7B4EC545AE5 (pass_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE parcauto_echeance_entretien (id INT AUTO_INCREMENT NOT NULL, effectue_le DATETIME(6) DEFAULT NULL, kilometrage_controle INT DEFAULT NULL, intervalle_mois INT DEFAULT NULL, intervalle_km INT DEFAULT NULL, type VARCHAR(30) NOT NULL, libelle VARCHAR(150) NOT NULL, echeance DATETIME(6) NOT NULL, echeance_km INT DEFAULT NULL, vehicule_id INT NOT NULL, UNIQUE INDEX uniq_parcauto_vehicule_entretien (vehicule_id, type, libelle), INDEX IDX_FBDA2FE54A4A3511 (vehicule_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE parcauto_valeur_referentiel (groupe VARCHAR(20) NOT NULL, code VARCHAR(30) NOT NULL, libelle VARCHAR(120) NOT NULL, protegee TINYINT NOT NULL, PRIMARY KEY (groupe, code)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE parcauto_vehicule (id INT AUTO_INCREMENT NOT NULL, mis_en_service_le DATETIME(6) DEFAULT NULL, reforme_prevue_le DATETIME(6) DEFAULT NULL, reforme_km INT DEFAULT NULL, achat_centimes INT NOT NULL, consommation_reference INT DEFAULT NULL, immatriculation VARCHAR(24) NOT NULL, libelle VARCHAR(150) NOT NULL, site VARCHAR(120) NOT NULL, energie VARCHAR(30) NOT NULL, kilometrage INT NOT NULL, actif TINYINT NOT NULL, UNIQUE INDEX UNIQ_8469C777BE73422E (immatriculation), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE parcauto_reservation (motif_refus VARCHAR(255) DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, statut VARCHAR(16) NOT NULL, depart_le DATETIME(6) DEFAULT NULL, retour_le DATETIME(6) DEFAULT NULL, kilometrage_depart INT DEFAULT NULL, kilometrage_retour INT DEFAULT NULL, nom_conducteur VARCHAR(200) NOT NULL, debut DATETIME(6) NOT NULL, fin DATETIME(6) NOT NULL, ordre_mission VARCHAR(120) DEFAULT NULL, type_om VARCHAR(10) DEFAULT NULL, motif VARCHAR(255) DEFAULT NULL, destinations VARCHAR(500) DEFAULT NULL, urgence TINYINT DEFAULT 0 NOT NULL, ordre_verifie TINYINT NOT NULL, vehicule_id INT NOT NULL, conducteur_id INT NOT NULL, INDEX idx_parcauto_reservation_planning (vehicule_id, statut, debut, fin), INDEX IDX_8FA85F504A4A3511 (vehicule_id), INDEX IDX_8FA85F50F16F4AC6 (conducteur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE parcauto_reservation_equipement (reservation_id INT NOT NULL, equipement_id INT NOT NULL, INDEX IDX_3E3981E8B83297E7 (reservation_id), INDEX IDX_3E3981E8806F0F5C (equipement_id), PRIMARY KEY (reservation_id, equipement_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE parcauto_equipement (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(20) NOT NULL, reference VARCHAR(120) NOT NULL, libelle VARCHAR(150) NOT NULL, coordonnees VARCHAR(255) NOT NULL, actif TINYINT NOT NULL, portatif TINYINT DEFAULT 0 NOT NULL, sortie_id INT DEFAULT NULL, vehicule_id INT DEFAULT NULL, UNIQUE INDEX uniq_parcauto_equipement (type, reference), INDEX IDX_FA45006CC72D953 (sortie_id), INDEX IDX_FA450064A4A3511 (vehicule_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE parcauto_journal_vehicule ADD CONSTRAINT FK_728EB7B44A4A3511 FOREIGN KEY (vehicule_id) REFERENCES parcauto_vehicule (id)');
        $this->addSql('ALTER TABLE parcauto_journal_vehicule ADD CONSTRAINT FK_728EB7B460BB6FE6 FOREIGN KEY (auteur_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE parcauto_journal_vehicule ADD CONSTRAINT FK_728EB7B4BE3DB2B7 FOREIGN KEY (prestataire_id) REFERENCES parcauto_equipement (id)');
        $this->addSql('ALTER TABLE parcauto_journal_vehicule ADD CONSTRAINT FK_728EB7B4C9C7CEB6 FOREIGN KEY (carte_id) REFERENCES parcauto_equipement (id)');
        $this->addSql('ALTER TABLE parcauto_journal_vehicule ADD CONSTRAINT FK_728EB7B4B83297E7 FOREIGN KEY (reservation_id) REFERENCES parcauto_reservation (id)');
        $this->addSql('ALTER TABLE parcauto_journal_vehicule ADD CONSTRAINT FK_728EB7B4EC545AE5 FOREIGN KEY (pass_id) REFERENCES parcauto_equipement (id)');
        $this->addSql('ALTER TABLE parcauto_echeance_entretien ADD CONSTRAINT FK_FBDA2FE54A4A3511 FOREIGN KEY (vehicule_id) REFERENCES parcauto_vehicule (id)');
        $this->addSql('ALTER TABLE parcauto_reservation ADD CONSTRAINT FK_8FA85F504A4A3511 FOREIGN KEY (vehicule_id) REFERENCES parcauto_vehicule (id)');
        $this->addSql('ALTER TABLE parcauto_reservation ADD CONSTRAINT FK_8FA85F50F16F4AC6 FOREIGN KEY (conducteur_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE parcauto_reservation_equipement ADD CONSTRAINT FK_3E3981E8B83297E7 FOREIGN KEY (reservation_id) REFERENCES parcauto_reservation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE parcauto_reservation_equipement ADD CONSTRAINT FK_3E3981E8806F0F5C FOREIGN KEY (equipement_id) REFERENCES parcauto_equipement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE parcauto_equipement ADD CONSTRAINT FK_FA45006CC72D953 FOREIGN KEY (sortie_id) REFERENCES parcauto_reservation (id)');
        $this->addSql('ALTER TABLE parcauto_equipement ADD CONSTRAINT FK_FA450064A4A3511 FOREIGN KEY (vehicule_id) REFERENCES parcauto_vehicule (id)');
        // Valeurs de référence initiales.
        $this->addSql("INSERT INTO parcauto_valeur_referentiel (groupe, code, libelle, protegee) VALUES
            ('energie', 'essence', 'Essence', 0),
            ('energie', 'diesel', 'Diesel', 0),
            ('energie', 'hybride', 'Hybride', 0),
            ('energie', 'electrique', 'Électrique', 0),
            ('energie', 'autre', 'Autre', 0),
            ('equipement', 'prestataire', 'Prestataire', 1),
            ('equipement', 'carte', 'Carte carburant', 1),
            ('equipement', 'borne', 'Borne URCA', 1),
            ('plan', 'controle_technique', 'Contrôle technique', 1),
            ('plan', 'revision', 'Révision', 0),
            ('plan', 'pneus', 'Contrôle des pneus', 1),
            ('plan', 'entretien', 'Entretien constructeur', 0),
            ('journal', 'carburant', 'Plein de carburant', 1),
            ('journal', 'facture', 'Facture', 1),
            ('journal', 'pneus', 'Mesure d’un train de pneus', 1),
            ('journal', 'remplacement_pneus', 'Retrait d’un train de pneus', 1),
            ('journal', 'incident', 'Incident mécanique', 1),
            ('journal', 'remise_service', 'Remise en service', 1),
            ('journal', 'entretien', 'Entretien', 1)");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('La migration initiale ne s’annule pas.');
    }
}
