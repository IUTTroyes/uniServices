<?php

declare(strict_types=1);

namespace FinanceBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Schéma initial (MySQL) : crée les tables sur une base vide, ne touche pas à une base existante. */
final class Version20261127000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Schéma initial : FinanceBundle';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        // Base existante : la migration est enregistrée comme exécutée, sans aucune requête.
        if ($schema->hasTable('finance_bon_commande')) {
            $this->write('Tables déjà présentes : rien à créer.');

            return;
        }

        $this->addSql('CREATE TABLE finance_reception (id INT AUTO_INCREMENT NOT NULL, date_effective_reception DATETIME(6) DEFAULT NULL, type_reception VARCHAR(20) NOT NULL, montant_ttc_constate NUMERIC(10, 2) DEFAULT NULL, prestations_conformes TINYINT DEFAULT 1 NOT NULL, details_reception_partielle LONGTEXT DEFAULT NULL, numeros_migo JSON DEFAULT NULL, date_migo103 DATETIME(6) DEFAULT NULL, date_migo105 DATETIME(6) DEFAULT NULL, bl_rattache_sifac TINYINT DEFAULT 0 NOT NULL, qualite_receptionnaire VARCHAR(100) DEFAULT NULL, date_signature_service_fait DATETIME(6) DEFAULT NULL, date_visa DATETIME(6) DEFAULT NULL, bon_commande_id INT NOT NULL, receptionnaire_id INT DEFAULT NULL, viseur_id INT DEFAULT NULL, INDEX IDX_3301BF03B4B54061 (bon_commande_id), INDEX IDX_3301BF03B29F59C9 (receptionnaire_id), INDEX IDX_3301BF0346A95AB4 (viseur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE finance_taux_tva (id INT AUTO_INCREMENT NOT NULL, taux NUMERIC(5, 2) NOT NULL, libelle VARCHAR(100) NOT NULL, actif TINYINT DEFAULT 1 NOT NULL, par_defaut TINYINT DEFAULT 0 NOT NULL, ordre INT DEFAULT 0 NOT NULL, UNIQUE INDEX UNIQ_A0A495F5809A3D7D (taux), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE finance_reception_document (id INT AUTO_INCREMENT NOT NULL, chemin VARCHAR(255) NOT NULL, nom_fichier VARCHAR(255) NOT NULL, date_depot DATETIME(6) NOT NULL, reception_id INT NOT NULL, depose_par_id INT DEFAULT NULL, INDEX IDX_E46B496F7C14DF52 (reception_id), INDEX IDX_E46B496FDCFF0FC4 (depose_par_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE finance_bon_commande (id INT AUTO_INCREMENT NOT NULL, prestation VARCHAR(20) NOT NULL, objet_demande VARCHAR(255) NOT NULL, montant_ht NUMERIC(10, 2) DEFAULT NULL, montant_ttc NUMERIC(10, 2) DEFAULT NULL, taux_tva NUMERIC(5, 2) DEFAULT NULL, fournisseur_propose VARCHAR(255) DEFAULT NULL, note_explicative LONGTEXT DEFAULT NULL, date_demande DATETIME(6) DEFAULT NULL, date_signature_demandeur DATETIME(6) DEFAULT NULL, date_signature_responsable DATETIME(6) DEFAULT NULL, avis_directeur VARCHAR(30) DEFAULT NULL, avis_directeur_commentaire LONGTEXT DEFAULT NULL, date_avis_directeur DATETIME(6) DEFAULT NULL, centre_financier VARCHAR(50) DEFAULT NULL, commande_sur_marche TINYINT DEFAULT 0 NOT NULL, numero_bon_commande VARCHAR(50) DEFAULT NULL, date_bon_commande DATETIME(6) DEFAULT NULL, date_livraison_renseignee TINYINT DEFAULT 0 NOT NULL, pj_sur_sifac TINYINT DEFAULT 0 NOT NULL, bc_transmis TINYINT DEFAULT 0 NOT NULL, colis_attendu VARCHAR(100) DEFAULT NULL, date_verification_financiere DATETIME(6) DEFAULT NULL, date_visa_engagement DATETIME(6) DEFAULT NULL, date_certification_service_fait DATETIME(6) DEFAULT NULL, date_arrivee_facture DATETIME(6) DEFAULT NULL, rejet_facture_motif LONGTEXT DEFAULT NULL, date_paiement DATETIME(6) DEFAULT NULL, facture_finale TINYINT DEFAULT 0 NOT NULL, motif_rejet LONGTEXT DEFAULT NULL, statut VARCHAR(30) NOT NULL, pdf_file_path VARCHAR(255) DEFAULT NULL, created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, demandeur_id INT DEFAULT NULL, responsable_id INT DEFAULT NULL, departement_id INT DEFAULT NULL, service_id INT DEFAULT NULL, signataire_directeur_id INT DEFAULT NULL, gestionnaire_id INT DEFAULT NULL, fournisseur_id INT DEFAULT NULL, verificateur_financier_id INT DEFAULT NULL, signataire_visa_engagement_id INT DEFAULT NULL, certificateur_service_fait_id INT DEFAULT NULL, INDEX IDX_19D4B39F95A6EE59 (demandeur_id), INDEX IDX_19D4B39F53C59D72 (responsable_id), INDEX IDX_19D4B39FCCF9E01E (departement_id), INDEX IDX_19D4B39FED5CA9E6 (service_id), INDEX IDX_19D4B39FA7E2BED6 (signataire_directeur_id), INDEX IDX_19D4B39F6885AC1B (gestionnaire_id), INDEX IDX_19D4B39F670C757F (fournisseur_id), INDEX IDX_19D4B39F11D37082 (verificateur_financier_id), INDEX IDX_19D4B39F82EBEE88 (signataire_visa_engagement_id), INDEX IDX_19D4B39FBD4166FB (certificateur_service_fait_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE finance_fournisseur (id INT AUTO_INCREMENT NOT NULL, numero_fournisseur VARCHAR(50) DEFAULT NULL, nom_fournisseur VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE finance_reception ADD CONSTRAINT FK_3301BF03B4B54061 FOREIGN KEY (bon_commande_id) REFERENCES finance_bon_commande (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE finance_reception ADD CONSTRAINT FK_3301BF03B29F59C9 FOREIGN KEY (receptionnaire_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE finance_reception ADD CONSTRAINT FK_3301BF0346A95AB4 FOREIGN KEY (viseur_id) REFERENCES personnel (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE finance_reception_document ADD CONSTRAINT FK_E46B496F7C14DF52 FOREIGN KEY (reception_id) REFERENCES finance_reception (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE finance_reception_document ADD CONSTRAINT FK_E46B496FDCFF0FC4 FOREIGN KEY (depose_par_id) REFERENCES personnel (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE finance_bon_commande ADD CONSTRAINT FK_19D4B39F95A6EE59 FOREIGN KEY (demandeur_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE finance_bon_commande ADD CONSTRAINT FK_19D4B39F53C59D72 FOREIGN KEY (responsable_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE finance_bon_commande ADD CONSTRAINT FK_19D4B39FCCF9E01E FOREIGN KEY (departement_id) REFERENCES structure_departement (id)');
        $this->addSql('ALTER TABLE finance_bon_commande ADD CONSTRAINT FK_19D4B39FED5CA9E6 FOREIGN KEY (service_id) REFERENCES structure_service (id)');
        $this->addSql('ALTER TABLE finance_bon_commande ADD CONSTRAINT FK_19D4B39FA7E2BED6 FOREIGN KEY (signataire_directeur_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE finance_bon_commande ADD CONSTRAINT FK_19D4B39F6885AC1B FOREIGN KEY (gestionnaire_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE finance_bon_commande ADD CONSTRAINT FK_19D4B39F670C757F FOREIGN KEY (fournisseur_id) REFERENCES finance_fournisseur (id)');
        $this->addSql('ALTER TABLE finance_bon_commande ADD CONSTRAINT FK_19D4B39F11D37082 FOREIGN KEY (verificateur_financier_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE finance_bon_commande ADD CONSTRAINT FK_19D4B39F82EBEE88 FOREIGN KEY (signataire_visa_engagement_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE finance_bon_commande ADD CONSTRAINT FK_19D4B39FBD4166FB FOREIGN KEY (certificateur_service_fait_id) REFERENCES personnel (id)');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('La migration initiale ne s’annule pas.');
    }
}
