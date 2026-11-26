<?php

declare(strict_types=1);

namespace IntranetBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Schéma initial (MySQL) : crée les tables sur une base vide, ne touche pas à une base existante. */
final class Version20261127000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Schéma initial : IntranetBundle';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        // Base existante : la migration est enregistrée comme exécutée, sans aucune requête.
        if ($schema->hasTable('edt_appel')) {
            $this->write('Tables déjà présentes : rien à créer.');

            return;
        }

        $this->addSql('CREATE TABLE edt_appel (id INT AUTO_INCREMENT NOT NULL, etat TINYINT NOT NULL, created DATETIME(6) NOT NULL, updated DATETIME(6) DEFAULT NULL, edt_event_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_86B114BE6DB5A46A (edt_event_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE intranet_sous_commission_revision (id INT AUTO_INCREMENT NOT NULL, numero INT NOT NULL, valide_le DATETIME(6) NOT NULL, vue JSON NOT NULL, sources JSON NOT NULL, empreinte VARCHAR(64) NOT NULL, invalidee_le DATETIME(6) DEFAULT NULL, motif_reouverture LONGTEXT DEFAULT NULL, motif_restauration LONGTEXT DEFAULT NULL, created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, sous_commission_id INT NOT NULL, valide_par_id INT NOT NULL, rouvert_par_id INT DEFAULT NULL, restauree_depuis_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_COMMISSION_REVISION (sous_commission_id, numero), INDEX IDX_FEB9E65E1D4A1B3D (sous_commission_id), INDEX IDX_FEB9E65E6AF12ED9 (valide_par_id), INDEX IDX_FEB9E65EBA1D6A4A (rouvert_par_id), INDEX IDX_FEB9E65E255DEF7F (restauree_depuis_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE intranet_sous_commission (id INT AUTO_INCREMENT NOT NULL, validee TINYINT NOT NULL, numero_revision INT NOT NULL, created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, semestre_id INT NOT NULL, UNIQUE INDEX UNIQ_73535F8E5577AFDB (semestre_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE intranet_resultat_sous_commission (id INT AUTO_INCREMENT NOT NULL, resultat JSON NOT NULL, proposition_calculee TINYINT DEFAULT NULL, decision_jury TINYINT NOT NULL, motif_jury LONGTEXT DEFAULT NULL, revision_id INT NOT NULL, inscription_id INT NOT NULL, UNIQUE INDEX UNIQ_REVISION_INSCRIPTION (revision_id, inscription_id), INDEX IDX_9703E2571DFA7C8F (revision_id), INDEX IDX_9703E2575DAC5993 (inscription_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE moyenne_enseignement (id INT AUTO_INCREMENT NOT NULL, value DOUBLE PRECISION NOT NULL, enseignement_id INT NOT NULL, etudiant_scolarite_semestre_id INT NOT NULL, personnel_id INT NOT NULL, INDEX IDX_E8BFA5F1ABEC3B20 (enseignement_id), INDEX IDX_E8BFA5F121C2A682 (etudiant_scolarite_semestre_id), INDEX IDX_E8BFA5F11C109075 (personnel_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE etudiant_absence (id INT AUTO_INCREMENT NOT NULL, date_justification DATETIME(6) DEFAULT NULL, uuid BINARY(16) NOT NULL, key_edu_sign VARCHAR(255) DEFAULT NULL, created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, personnel_id INT DEFAULT NULL, absence_justificatif_id INT DEFAULT NULL, scolarite_semestre_id INT DEFAULT NULL, event_id INT NOT NULL, INDEX IDX_A2F5AE4D1C109075 (personnel_id), INDEX IDX_A2F5AE4D7A325CAA (absence_justificatif_id), INDEX IDX_A2F5AE4D4992EB30 (scolarite_semestre_id), INDEX IDX_A2F5AE4D71F7E88B (event_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE etudiant_absence_justificatif (id INT AUTO_INCREMENT NOT NULL, debut DATETIME(6) NOT NULL, fin DATETIME(6) NOT NULL, motif LONGTEXT DEFAULT NULL, motif_refus LONGTEXT DEFAULT NULL, etat SMALLINT NOT NULL, fichier VARCHAR(255) DEFAULT NULL, nom_fichier VARCHAR(255) DEFAULT NULL, uuid BINARY(16) NOT NULL, scolarite_semestre_id INT NOT NULL, INDEX IDX_F4D720644992EB30 (scolarite_semestre_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE moyenne_ue (id INT AUTO_INCREMENT NOT NULL, value DOUBLE PRECISION NOT NULL, ue_id INT NOT NULL, etudiant_scolarite_semestre_id INT NOT NULL, personnel_id INT NOT NULL, INDEX IDX_3EAC91E462E883B1 (ue_id), INDEX IDX_3EAC91E421C2A682 (etudiant_scolarite_semestre_id), INDEX IDX_3EAC91E41C109075 (personnel_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE previsionnel (id INT AUTO_INCREMENT NOT NULL, referent TINYINT NOT NULL, heures JSON NOT NULL, groupes JSON NOT NULL, personnel_id INT DEFAULT NULL, annee_universitaire_id INT DEFAULT NULL, enseignement_id INT DEFAULT NULL, progression_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_AE25C261AF229C18 (progression_id), INDEX IDX_AE25C2611C109075 (personnel_id), INDEX IDX_AE25C261544BFD58 (annee_universitaire_id), INDEX IDX_AE25C261ABEC3B20 (enseignement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE intranet_avancement_enseignement (id INT AUTO_INCREMENT NOT NULL, auteur_libelle VARCHAR(255) NOT NULL, repere LONGTEXT NOT NULL, prochaine_etape LONGTEXT NOT NULL, sequence_snapshot JSON DEFAULT NULL, etat_sequence VARCHAR(20) DEFAULT NULL, created_at DATETIME(6) NOT NULL, enseignement_id INT NOT NULL, groupe_id INT NOT NULL, annee_universitaire_id INT NOT NULL, auteur_id INT DEFAULT NULL, INDEX idx_avancement_perimetre (enseignement_id, annee_universitaire_id, groupe_id, id), INDEX IDX_F3FC587ABEC3B20 (enseignement_id), INDEX IDX_F3FC5877A45358C (groupe_id), INDEX IDX_F3FC587544BFD58 (annee_universitaire_id), INDEX IDX_F3FC58760BB6FE6 (auteur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE plan_cours (id INT AUTO_INCREMENT NOT NULL, etat_plan_cours VARCHAR(30) NOT NULL, nb_notes INT NOT NULL, cm_prevu DOUBLE PRECISION DEFAULT NULL, td_prevu DOUBLE PRECISION DEFAULT NULL, tp_prevu DOUBLE PRECISION DEFAULT NULL, projet_prevu DOUBLE PRECISION DEFAULT NULL, cm_realise DOUBLE PRECISION DEFAULT NULL, td_realise DOUBLE PRECISION DEFAULT NULL, tp_realise DOUBLE PRECISION DEFAULT NULL, projet_realise DOUBLE PRECISION DEFAULT NULL, bibliographie LONGTEXT DEFAULT NULL, description LONGTEXT DEFAULT NULL, objectifs LONGTEXT DEFAULT NULL, modalites_evaluations LONGTEXT DEFAULT NULL, livrables LONGTEXT DEFAULT NULL, mise_en_oeuvre LONGTEXT DEFAULT NULL, modalite_encadrement_autonomie LONGTEXT DEFAULT NULL, saisie_plan_cours LONGTEXT DEFAULT NULL, fichier_plan_cours VARCHAR(255) DEFAULT NULL, plan_suivi TINYINT DEFAULT NULL, plan_suivi_commentaire LONGTEXT DEFAULT NULL, objectifs_atteints TINYINT DEFAULT NULL, objectifs_atteints_commentaire LONGTEXT DEFAULT NULL, competences_acquises TINYINT DEFAULT NULL, competences_acquises_commentaire LONGTEXT DEFAULT NULL, mode_evaluation_commentaire LONGTEXT DEFAULT NULL, commentaires_step1 LONGTEXT DEFAULT NULL, commentaires_step2 LONGTEXT DEFAULT NULL, commentaires_step3 LONGTEXT DEFAULT NULL, created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, enseignement_id INT NOT NULL, annee_universitaire_id INT NOT NULL, responsable_id INT DEFAULT NULL, INDEX IDX_AE6CC960ABEC3B20 (enseignement_id), INDEX IDX_AE6CC960544BFD58 (annee_universitaire_id), INDEX IDX_AE6CC96053C59D72 (responsable_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE plan_cours_intervenants (plan_cours_id INT NOT NULL, personnel_id INT NOT NULL, INDEX IDX_D5A662B70AA4FCA (plan_cours_id), INDEX IDX_D5A662B1C109075 (personnel_id), PRIMARY KEY (plan_cours_id, personnel_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE plan_cours_realise (id INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT NOT NULL, nb_heures DOUBLE PRECISION DEFAULT NULL, commentaire LONGTEXT DEFAULT NULL, created DATETIME(6) NOT NULL, updated DATETIME(6) DEFAULT NULL, plan_cours_id INT NOT NULL, INDEX IDX_58CBACD670AA4FCA (plan_cours_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE plan_cours_sequence (id INT AUTO_INCREMENT NOT NULL, ordre INT DEFAULT NULL, programme LONGTEXT NOT NULL, nb_heures VARCHAR(100) DEFAULT NULL, commentaire LONGTEXT DEFAULT NULL, created DATETIME(6) NOT NULL, updated DATETIME(6) DEFAULT NULL, plan_cours_id INT NOT NULL, INDEX IDX_2ACC19FC70AA4FCA (plan_cours_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE edt_appel ADD CONSTRAINT FK_86B114BE6DB5A46A FOREIGN KEY (edt_event_id) REFERENCES edt_event (id)');
        $this->addSql('ALTER TABLE intranet_sous_commission_revision ADD CONSTRAINT FK_FEB9E65E1D4A1B3D FOREIGN KEY (sous_commission_id) REFERENCES intranet_sous_commission (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE intranet_sous_commission_revision ADD CONSTRAINT FK_FEB9E65E6AF12ED9 FOREIGN KEY (valide_par_id) REFERENCES personnel (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE intranet_sous_commission_revision ADD CONSTRAINT FK_FEB9E65EBA1D6A4A FOREIGN KEY (rouvert_par_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE intranet_sous_commission_revision ADD CONSTRAINT FK_FEB9E65E255DEF7F FOREIGN KEY (restauree_depuis_id) REFERENCES intranet_sous_commission_revision (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE intranet_sous_commission ADD CONSTRAINT FK_73535F8E5577AFDB FOREIGN KEY (semestre_id) REFERENCES structure_semestre (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE intranet_resultat_sous_commission ADD CONSTRAINT FK_9703E2571DFA7C8F FOREIGN KEY (revision_id) REFERENCES intranet_sous_commission_revision (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE intranet_resultat_sous_commission ADD CONSTRAINT FK_9703E2575DAC5993 FOREIGN KEY (inscription_id) REFERENCES etudiant_scolarite_semestre (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE moyenne_enseignement ADD CONSTRAINT FK_E8BFA5F1ABEC3B20 FOREIGN KEY (enseignement_id) REFERENCES scol_enseignement (id)');
        $this->addSql('ALTER TABLE moyenne_enseignement ADD CONSTRAINT FK_E8BFA5F121C2A682 FOREIGN KEY (etudiant_scolarite_semestre_id) REFERENCES etudiant_scolarite_semestre (id)');
        $this->addSql('ALTER TABLE moyenne_enseignement ADD CONSTRAINT FK_E8BFA5F11C109075 FOREIGN KEY (personnel_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE etudiant_absence ADD CONSTRAINT FK_A2F5AE4D1C109075 FOREIGN KEY (personnel_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE etudiant_absence ADD CONSTRAINT FK_A2F5AE4D7A325CAA FOREIGN KEY (absence_justificatif_id) REFERENCES etudiant_absence_justificatif (id)');
        $this->addSql('ALTER TABLE etudiant_absence ADD CONSTRAINT FK_A2F5AE4D4992EB30 FOREIGN KEY (scolarite_semestre_id) REFERENCES etudiant_scolarite_semestre (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE etudiant_absence ADD CONSTRAINT FK_A2F5AE4D71F7E88B FOREIGN KEY (event_id) REFERENCES edt_event (id)');
        $this->addSql('ALTER TABLE etudiant_absence_justificatif ADD CONSTRAINT FK_F4D720644992EB30 FOREIGN KEY (scolarite_semestre_id) REFERENCES etudiant_scolarite_semestre (id)');
        $this->addSql('ALTER TABLE moyenne_ue ADD CONSTRAINT FK_3EAC91E462E883B1 FOREIGN KEY (ue_id) REFERENCES structure_ue (id)');
        $this->addSql('ALTER TABLE moyenne_ue ADD CONSTRAINT FK_3EAC91E421C2A682 FOREIGN KEY (etudiant_scolarite_semestre_id) REFERENCES etudiant_scolarite_semestre (id)');
        $this->addSql('ALTER TABLE moyenne_ue ADD CONSTRAINT FK_3EAC91E41C109075 FOREIGN KEY (personnel_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE previsionnel ADD CONSTRAINT FK_AE25C2611C109075 FOREIGN KEY (personnel_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE previsionnel ADD CONSTRAINT FK_AE25C261544BFD58 FOREIGN KEY (annee_universitaire_id) REFERENCES structure_annee_universitaire (id)');
        $this->addSql('ALTER TABLE previsionnel ADD CONSTRAINT FK_AE25C261ABEC3B20 FOREIGN KEY (enseignement_id) REFERENCES scol_enseignement (id)');
        $this->addSql('ALTER TABLE previsionnel ADD CONSTRAINT FK_AE25C261AF229C18 FOREIGN KEY (progression_id) REFERENCES edt_progression (id)');
        $this->addSql('ALTER TABLE intranet_avancement_enseignement ADD CONSTRAINT FK_F3FC587ABEC3B20 FOREIGN KEY (enseignement_id) REFERENCES scol_enseignement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE intranet_avancement_enseignement ADD CONSTRAINT FK_F3FC5877A45358C FOREIGN KEY (groupe_id) REFERENCES structure_groupe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE intranet_avancement_enseignement ADD CONSTRAINT FK_F3FC587544BFD58 FOREIGN KEY (annee_universitaire_id) REFERENCES structure_annee_universitaire (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE intranet_avancement_enseignement ADD CONSTRAINT FK_F3FC58760BB6FE6 FOREIGN KEY (auteur_id) REFERENCES personnel (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE plan_cours ADD CONSTRAINT FK_AE6CC960ABEC3B20 FOREIGN KEY (enseignement_id) REFERENCES scol_enseignement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE plan_cours ADD CONSTRAINT FK_AE6CC960544BFD58 FOREIGN KEY (annee_universitaire_id) REFERENCES structure_annee_universitaire (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE plan_cours ADD CONSTRAINT FK_AE6CC96053C59D72 FOREIGN KEY (responsable_id) REFERENCES personnel (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE plan_cours_intervenants ADD CONSTRAINT FK_D5A662B70AA4FCA FOREIGN KEY (plan_cours_id) REFERENCES plan_cours (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE plan_cours_intervenants ADD CONSTRAINT FK_D5A662B1C109075 FOREIGN KEY (personnel_id) REFERENCES personnel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE plan_cours_realise ADD CONSTRAINT FK_58CBACD670AA4FCA FOREIGN KEY (plan_cours_id) REFERENCES plan_cours (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE plan_cours_sequence ADD CONSTRAINT FK_2ACC19FC70AA4FCA FOREIGN KEY (plan_cours_id) REFERENCES plan_cours (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('La migration initiale ne s’annule pas.');
    }
}
