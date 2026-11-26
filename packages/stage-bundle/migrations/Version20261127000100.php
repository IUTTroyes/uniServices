<?php

declare(strict_types=1);

namespace StageBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Schéma initial (MySQL) : crée les tables sur une base vide, ne touche pas à une base existante. */
final class Version20261127000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Schéma initial : StageBundle';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        // Base existante : la migration est enregistrée comme exécutée, sans aucune requête.
        if ($schema->hasTable('stage_avenant')) {
            $this->write('Tables déjà présentes : rien à créer.');

            return;
        }

        $this->addSql('CREATE TABLE stage_avenant (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(150) NOT NULL, texte LONGTEXT NOT NULL, type_modification VARCHAR(25) NOT NULL, valide TINYINT NOT NULL, date_creation DATETIME(6) NOT NULL, date_validation DATETIME(6) DEFAULT NULL, new_date_debut DATETIME(6) DEFAULT NULL, new_date_fin DATETIME(6) DEFAULT NULL, new_duree_hebdomadaire DOUBLE PRECISION DEFAULT NULL, new_gratification_montant DOUBLE PRECISION DEFAULT NULL, created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, stage_etudiant_id INT NOT NULL, new_tuteur_id INT DEFAULT NULL, INDEX IDX_88071CA8C1D8C49 (stage_etudiant_id), INDEX IDX_88071CA8B4736DB7 (new_tuteur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE stage_soutenance (id INT AUTO_INCREMENT NOT NULL, date_soutenance DATETIME(6) NOT NULL, salle VARCHAR(100) DEFAULT NULL, duree INT NOT NULL, created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, stage_etudiant_id INT DEFAULT NULL, stage_periode_id INT NOT NULL, enseignant_jury_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_103E4CAC1D8C49 (stage_etudiant_id), INDEX IDX_103E4CABFDEDA6B (stage_periode_id), INDEX IDX_103E4CA46A5E752 (enseignant_jury_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE stage_evaluation_critere (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(150) NOT NULL, description LONGTEXT DEFAULT NULL, ordre INT DEFAULT 0 NOT NULL, stage_periode_id INT NOT NULL, INDEX IDX_27B12742BFDEDA6B (stage_periode_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE stage_contexte (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, stage_etudiant_id INT NOT NULL, UNIQUE INDEX UNIQ_5062BF89C1D8C49 (stage_etudiant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE stage_etudiant (id INT AUTO_INCREMENT NOT NULL, service_stage_entreprise VARCHAR(255) DEFAULT NULL, sujet_stage LONGTEXT DEFAULT NULL, date_depot_formulaire DATETIME(6) DEFAULT NULL, date_validation DATETIME(6) DEFAULT NULL, date_convention_envoyee DATETIME(6) DEFAULT NULL, date_convention_recu DATETIME(6) DEFAULT NULL, etat_stage VARCHAR(50) NOT NULL, motif_refus LONGTEXT DEFAULT NULL, date_debut_stage DATETIME(6) DEFAULT NULL, date_fin_stage DATETIME(6) DEFAULT NULL, activites LONGTEXT DEFAULT NULL, amenagement_stage LONGTEXT DEFAULT NULL, gratification TINYINT NOT NULL, gratification_montant DOUBLE PRECISION DEFAULT NULL, gratification_periode VARCHAR(1) DEFAULT NULL, avantages LONGTEXT DEFAULT NULL, duree_hebdomadaire DOUBLE PRECISION NOT NULL, duree_jours_stage INT NOT NULL, assurance_compagnie VARCHAR(255) DEFAULT NULL, assurance_numero VARCHAR(255) DEFAULT NULL, date_autorise DATETIME(6) DEFAULT NULL, date_imprime DATETIME(6) DEFAULT NULL, adresse_stage JSON DEFAULT NULL, periodes_interruptions LONGTEXT DEFAULT NULL, commentaire_duree_hebdomadaire LONGTEXT DEFAULT NULL, evaluation_note DOUBLE PRECISION DEFAULT NULL, evaluation_commentaire LONGTEXT DEFAULT NULL, suivi_rencontres JSON DEFAULT NULL, report_uploaded TINYINT NOT NULL, report_name VARCHAR(255) DEFAULT NULL, report_path VARCHAR(255) DEFAULT NULL, report_date DATETIME(6) DEFAULT NULL, anonymise_le DATETIME(6) DEFAULT NULL, uuid BINARY(16) NOT NULL, created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, stage_periode_id INT DEFAULT NULL, etudiant_id INT DEFAULT NULL, tuteur_id INT DEFAULT NULL, tuteur_universitaire_id INT DEFAULT NULL, entreprise_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_7999E68E86EC68D8 (tuteur_id), INDEX IDX_7999E68EBFDEDA6B (stage_periode_id), INDEX IDX_7999E68EDDEAB1A3 (etudiant_id), INDEX IDX_7999E68E19FA6F23 (tuteur_universitaire_id), INDEX IDX_7999E68EA4AEAFEA (entreprise_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE stage_evaluation_entreprise (id INT AUTO_INCREMENT NOT NULL, jeton_hash VARCHAR(64) NOT NULL, envoye_a VARCHAR(255) NOT NULL, envoye_le DATETIME(6) NOT NULL, expire_le DATETIME(6) NOT NULL, soumis_le DATETIME(6) DEFAULT NULL, evaluateur_nom VARCHAR(150) DEFAULT NULL, reponses JSON DEFAULT NULL, appreciation LONGTEXT DEFAULT NULL, note_proposee DOUBLE PRECISION DEFAULT NULL, stage_etudiant_id INT NOT NULL, UNIQUE INDEX UNIQ_E0BA120D1E1E10D6 (jeton_hash), UNIQUE INDEX UNIQ_E0BA120DC1D8C49 (stage_etudiant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE stage_periode_soutenance (id INT AUTO_INCREMENT NOT NULL, date_debut DATETIME(6) NOT NULL, date_fin DATETIME(6) NOT NULL, date_rendu_rapport DATETIME(6) NOT NULL, modalites LONGTEXT DEFAULT NULL, stage_periode_id INT NOT NULL, INDEX IDX_DF1DB51FBFDEDA6B (stage_periode_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE stage_convention_template (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(50) NOT NULL, libelle VARCHAR(150) NOT NULL, texte LONGTEXT NOT NULL, UNIQUE INDEX UNIQ_701437EE77153098 (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE stage_contact (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(50) DEFAULT NULL, prenom VARCHAR(50) DEFAULT NULL, fonction VARCHAR(255) DEFAULT NULL, telephone VARCHAR(25) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, portable VARCHAR(25) DEFAULT NULL, civilite VARCHAR(3) DEFAULT NULL, fax VARCHAR(25) DEFAULT NULL, adresse JSON DEFAULT NULL, created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, old_id INT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE stage_periode (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(150) NOT NULL, nb_semaines INT NOT NULL, nb_jours INT NOT NULL, date_debut DATETIME(6) NOT NULL, date_fin DATETIME(6) NOT NULL, dates_flexibles TINYINT DEFAULT 0 NOT NULL, description LONGTEXT DEFAULT NULL, commentaire_libre LONGTEXT DEFAULT NULL, competences_visees LONGTEXT DEFAULT NULL, modalites_evaluation_entreprise LONGTEXT DEFAULT NULL, modalites_evaluation_pedagogique LONGTEXT DEFAULT NULL, modalites_encadrement LONGTEXT DEFAULT NULL, documents_rendre LONGTEXT DEFAULT NULL, consignes_fichiers JSON DEFAULT NULL, old_id INT DEFAULT NULL, annee_universitaire_id INT DEFAULT NULL, semestre_programme_id INT DEFAULT NULL, responsable_principal_id INT DEFAULT NULL, INDEX IDX_193A6DBE544BFD58 (annee_universitaire_id), INDEX IDX_193A6DBEE48600CE (semestre_programme_id), INDEX IDX_193A6DBE63FC2761 (responsable_principal_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE stage_periode_structure_semestre (stage_periode_id INT NOT NULL, structure_semestre_id INT NOT NULL, INDEX IDX_9FE3AAC5BFDEDA6B (stage_periode_id), INDEX IDX_9FE3AAC5C4218D78 (structure_semestre_id), PRIMARY KEY (stage_periode_id, structure_semestre_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE stage_periode_personnel (stage_periode_id INT NOT NULL, personnel_id INT NOT NULL, INDEX IDX_30781912BFDEDA6B (stage_periode_id), INDEX IDX_307819121C109075 (personnel_id), PRIMARY KEY (stage_periode_id, personnel_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE stage_entreprise (id INT AUTO_INCREMENT NOT NULL, siret VARCHAR(30) DEFAULT NULL, raison_sociale VARCHAR(255) NOT NULL, adresse JSON DEFAULT NULL, created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, old_id INT DEFAULT NULL, responsable_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_4143E1C453C59D72 (responsable_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE stage_periode_interruption (id INT AUTO_INCREMENT NOT NULL, date_debut DATETIME(6) NOT NULL, date_fin DATETIME(6) NOT NULL, motif VARCHAR(255) NOT NULL, stage_periode_id INT NOT NULL, INDEX IDX_597C69BDBFDEDA6B (stage_periode_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE stage_avenant ADD CONSTRAINT FK_88071CA8C1D8C49 FOREIGN KEY (stage_etudiant_id) REFERENCES stage_etudiant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stage_avenant ADD CONSTRAINT FK_88071CA8B4736DB7 FOREIGN KEY (new_tuteur_id) REFERENCES stage_contact (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE stage_soutenance ADD CONSTRAINT FK_103E4CAC1D8C49 FOREIGN KEY (stage_etudiant_id) REFERENCES stage_etudiant (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE stage_soutenance ADD CONSTRAINT FK_103E4CABFDEDA6B FOREIGN KEY (stage_periode_id) REFERENCES stage_periode (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stage_soutenance ADD CONSTRAINT FK_103E4CA46A5E752 FOREIGN KEY (enseignant_jury_id) REFERENCES personnel (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE stage_evaluation_critere ADD CONSTRAINT FK_27B12742BFDEDA6B FOREIGN KEY (stage_periode_id) REFERENCES stage_periode (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stage_contexte ADD CONSTRAINT FK_5062BF89C1D8C49 FOREIGN KEY (stage_etudiant_id) REFERENCES stage_etudiant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stage_etudiant ADD CONSTRAINT FK_7999E68EBFDEDA6B FOREIGN KEY (stage_periode_id) REFERENCES stage_periode (id)');
        $this->addSql('ALTER TABLE stage_etudiant ADD CONSTRAINT FK_7999E68EDDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES etudiant (id)');
        $this->addSql('ALTER TABLE stage_etudiant ADD CONSTRAINT FK_7999E68E86EC68D8 FOREIGN KEY (tuteur_id) REFERENCES stage_contact (id)');
        $this->addSql('ALTER TABLE stage_etudiant ADD CONSTRAINT FK_7999E68E19FA6F23 FOREIGN KEY (tuteur_universitaire_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE stage_etudiant ADD CONSTRAINT FK_7999E68EA4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES stage_entreprise (id)');
        $this->addSql('ALTER TABLE stage_evaluation_entreprise ADD CONSTRAINT FK_E0BA120DC1D8C49 FOREIGN KEY (stage_etudiant_id) REFERENCES stage_etudiant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stage_periode_soutenance ADD CONSTRAINT FK_DF1DB51FBFDEDA6B FOREIGN KEY (stage_periode_id) REFERENCES stage_periode (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stage_periode ADD CONSTRAINT FK_193A6DBE544BFD58 FOREIGN KEY (annee_universitaire_id) REFERENCES structure_annee_universitaire (id)');
        $this->addSql('ALTER TABLE stage_periode ADD CONSTRAINT FK_193A6DBEE48600CE FOREIGN KEY (semestre_programme_id) REFERENCES structure_semestre (id)');
        $this->addSql('ALTER TABLE stage_periode ADD CONSTRAINT FK_193A6DBE63FC2761 FOREIGN KEY (responsable_principal_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE stage_periode_structure_semestre ADD CONSTRAINT FK_9FE3AAC5BFDEDA6B FOREIGN KEY (stage_periode_id) REFERENCES stage_periode (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stage_periode_structure_semestre ADD CONSTRAINT FK_9FE3AAC5C4218D78 FOREIGN KEY (structure_semestre_id) REFERENCES structure_semestre (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stage_periode_personnel ADD CONSTRAINT FK_30781912BFDEDA6B FOREIGN KEY (stage_periode_id) REFERENCES stage_periode (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stage_periode_personnel ADD CONSTRAINT FK_307819121C109075 FOREIGN KEY (personnel_id) REFERENCES personnel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stage_entreprise ADD CONSTRAINT FK_4143E1C453C59D72 FOREIGN KEY (responsable_id) REFERENCES stage_contact (id)');
        $this->addSql('ALTER TABLE stage_periode_interruption ADD CONSTRAINT FK_597C69BDBFDEDA6B FOREIGN KEY (stage_periode_id) REFERENCES stage_periode (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('La migration initiale ne s’annule pas.');
    }
}
