<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261106194450 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'UniFolio lot 4 : campagnes, affectations, évaluations et commentaires';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE unifolio_campagne (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(255) NOT NULL, ouverture_le DATETIME(6) NOT NULL, depot_limite DATETIME(6) DEFAULT NULL, evaluation_limite DATETIME(6) DEFAULT NULL, cloture_le DATETIME(6) DEFAULT NULL, auto_evaluation_requise TINYINT DEFAULT 0 NOT NULL, created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, uuid BINARY(16) NOT NULL, diplome_id INT NOT NULL, annee_universitaire_id INT NOT NULL, annee_formation_id INT DEFAULT NULL, UNIQUE INDEX uniq_unifolio_campagne_uuid (uuid), INDEX IDX_9499EB0726F859E2 (diplome_id), INDEX IDX_9499EB07544BFD58 (annee_universitaire_id), INDEX IDX_9499EB073A687361 (annee_formation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE unifolio_campagne_affectation (id INT AUTO_INCREMENT NOT NULL, campagne_id INT NOT NULL, evaluateur_id INT NOT NULL, etudiant_id INT NOT NULL, INDEX idx_unifolio_affectation_evaluateur (evaluateur_id), UNIQUE INDEX uniq_unifolio_affectation (campagne_id, evaluateur_id, etudiant_id), INDEX IDX_702BAE5016227374 (campagne_id), INDEX IDX_702BAE50DDEAB1A3 (etudiant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE unifolio_commentaire (id INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT NOT NULL, created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, uuid BINARY(16) NOT NULL, trace_id INT NOT NULL, rattachement_id INT DEFAULT NULL, parent_id INT DEFAULT NULL, auteur_etudiant_id INT DEFAULT NULL, auteur_personnel_id INT DEFAULT NULL, UNIQUE INDEX uniq_unifolio_commentaire_uuid (uuid), INDEX IDX_2685C92EBE0D4B70 (trace_id), INDEX IDX_2685C92E8E780FD (rattachement_id), INDEX IDX_2685C92E727ACA70 (parent_id), INDEX IDX_2685C92EC9904620 (auteur_etudiant_id), INDEX IDX_2685C92E68B53818 (auteur_personnel_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE unifolio_evaluation (id INT AUTO_INCREMENT NOT NULL, appreciation LONGTEXT DEFAULT NULL, statut VARCHAR(20) DEFAULT \'en_cours\' NOT NULL, rendue_le DATETIME(6) DEFAULT NULL, visible_etudiant TINYINT DEFAULT 0 NOT NULL, score DOUBLE PRECISION DEFAULT NULL, created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, uuid BINARY(16) NOT NULL, campagne_id INT NOT NULL, evaluateur_id INT NOT NULL, etudiant_id INT NOT NULL, scolarite_id INT NOT NULL, grille_version_id INT NOT NULL, rattachement_id INT DEFAULT NULL, competence_id INT DEFAULT NULL, niveau_id INT DEFAULT NULL, apprentissage_critique_id INT DEFAULT NULL, UNIQUE INDEX uniq_unifolio_evaluation_uuid (uuid), UNIQUE INDEX uniq_unifolio_evaluation_rattachement (evaluateur_id, rattachement_id), INDEX IDX_62F857DE16227374 (campagne_id), INDEX IDX_62F857DE231F139 (evaluateur_id), INDEX IDX_62F857DEDDEAB1A3 (etudiant_id), INDEX IDX_62F857DEAA6B2AB6 (scolarite_id), INDEX IDX_62F857DE37F21E26 (grille_version_id), INDEX IDX_62F857DE8E780FD (rattachement_id), INDEX IDX_62F857DE15761DAB (competence_id), INDEX IDX_62F857DEB3E9C81 (niveau_id), INDEX IDX_62F857DE62399251 (apprentissage_critique_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE unifolio_campagne ADD CONSTRAINT FK_9499EB0726F859E2 FOREIGN KEY (diplome_id) REFERENCES structure_diplome (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_campagne ADD CONSTRAINT FK_9499EB07544BFD58 FOREIGN KEY (annee_universitaire_id) REFERENCES structure_annee_universitaire (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_campagne ADD CONSTRAINT FK_9499EB073A687361 FOREIGN KEY (annee_formation_id) REFERENCES structure_annee (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_campagne_affectation ADD CONSTRAINT FK_702BAE5016227374 FOREIGN KEY (campagne_id) REFERENCES unifolio_campagne (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_campagne_affectation ADD CONSTRAINT FK_702BAE50231F139 FOREIGN KEY (evaluateur_id) REFERENCES personnel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_campagne_affectation ADD CONSTRAINT FK_702BAE50DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES etudiant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_commentaire ADD CONSTRAINT FK_2685C92EBE0D4B70 FOREIGN KEY (trace_id) REFERENCES unifolio_trace (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_commentaire ADD CONSTRAINT FK_2685C92E8E780FD FOREIGN KEY (rattachement_id) REFERENCES unifolio_trace_rattachement (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE unifolio_commentaire ADD CONSTRAINT FK_2685C92E727ACA70 FOREIGN KEY (parent_id) REFERENCES unifolio_commentaire (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_commentaire ADD CONSTRAINT FK_2685C92EC9904620 FOREIGN KEY (auteur_etudiant_id) REFERENCES etudiant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_commentaire ADD CONSTRAINT FK_2685C92E68B53818 FOREIGN KEY (auteur_personnel_id) REFERENCES personnel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_evaluation ADD CONSTRAINT FK_62F857DE16227374 FOREIGN KEY (campagne_id) REFERENCES unifolio_campagne (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_evaluation ADD CONSTRAINT FK_62F857DE231F139 FOREIGN KEY (evaluateur_id) REFERENCES personnel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_evaluation ADD CONSTRAINT FK_62F857DEDDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES etudiant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_evaluation ADD CONSTRAINT FK_62F857DEAA6B2AB6 FOREIGN KEY (scolarite_id) REFERENCES etudiant_scolarite (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_evaluation ADD CONSTRAINT FK_62F857DE37F21E26 FOREIGN KEY (grille_version_id) REFERENCES unifolio_grille_version (id)');
        $this->addSql('ALTER TABLE unifolio_evaluation ADD CONSTRAINT FK_62F857DE8E780FD FOREIGN KEY (rattachement_id) REFERENCES unifolio_trace_rattachement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_evaluation ADD CONSTRAINT FK_62F857DE15761DAB FOREIGN KEY (competence_id) REFERENCES apc_competence (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_evaluation ADD CONSTRAINT FK_62F857DEB3E9C81 FOREIGN KEY (niveau_id) REFERENCES apc_niveau (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_evaluation ADD CONSTRAINT FK_62F857DE62399251 FOREIGN KEY (apprentissage_critique_id) REFERENCES apc_apprentissage_critique (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_reponse_critere ADD evaluation_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE unifolio_reponse_critere ADD CONSTRAINT FK_6A0D1DA7456C5646 FOREIGN KEY (evaluation_id) REFERENCES unifolio_evaluation (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_6A0D1DA7456C5646 ON unifolio_reponse_critere (evaluation_id)');
        // Exactement une cible par évaluation : un rattachement de trace, ou un élément du référentiel (bilan).
        $this->addSql('ALTER TABLE unifolio_evaluation ADD CONSTRAINT chk_unifolio_evaluation_cible CHECK ((rattachement_id IS NOT NULL) + (competence_id IS NOT NULL) + (niveau_id IS NOT NULL) + (apprentissage_critique_id IS NOT NULL) = 1)');
        // Une réponse appartient à une auto-évaluation OU à une évaluation.
        $this->addSql('ALTER TABLE unifolio_reponse_critere ADD CONSTRAINT chk_unifolio_reponse_parent CHECK ((auto_evaluation_id IS NOT NULL) + (evaluation_id IS NOT NULL) = 1)');
        // Un commentaire a un seul auteur : un étudiant ou un personnel.
        $this->addSql('ALTER TABLE unifolio_commentaire ADD CONSTRAINT chk_unifolio_commentaire_auteur CHECK ((auteur_etudiant_id IS NOT NULL) + (auteur_personnel_id IS NOT NULL) = 1)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE unifolio_commentaire DROP CHECK chk_unifolio_commentaire_auteur');
        $this->addSql('ALTER TABLE unifolio_reponse_critere DROP CHECK chk_unifolio_reponse_parent');
        $this->addSql('ALTER TABLE unifolio_evaluation DROP CHECK chk_unifolio_evaluation_cible');
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE unifolio_campagne DROP FOREIGN KEY FK_9499EB0726F859E2');
        $this->addSql('ALTER TABLE unifolio_campagne DROP FOREIGN KEY FK_9499EB07544BFD58');
        $this->addSql('ALTER TABLE unifolio_campagne DROP FOREIGN KEY FK_9499EB073A687361');
        $this->addSql('ALTER TABLE unifolio_campagne_affectation DROP FOREIGN KEY FK_702BAE5016227374');
        $this->addSql('ALTER TABLE unifolio_campagne_affectation DROP FOREIGN KEY FK_702BAE50231F139');
        $this->addSql('ALTER TABLE unifolio_campagne_affectation DROP FOREIGN KEY FK_702BAE50DDEAB1A3');
        $this->addSql('ALTER TABLE unifolio_commentaire DROP FOREIGN KEY FK_2685C92EBE0D4B70');
        $this->addSql('ALTER TABLE unifolio_commentaire DROP FOREIGN KEY FK_2685C92E8E780FD');
        $this->addSql('ALTER TABLE unifolio_commentaire DROP FOREIGN KEY FK_2685C92E727ACA70');
        $this->addSql('ALTER TABLE unifolio_commentaire DROP FOREIGN KEY FK_2685C92EC9904620');
        $this->addSql('ALTER TABLE unifolio_commentaire DROP FOREIGN KEY FK_2685C92E68B53818');
        $this->addSql('ALTER TABLE unifolio_evaluation DROP FOREIGN KEY FK_62F857DE16227374');
        $this->addSql('ALTER TABLE unifolio_evaluation DROP FOREIGN KEY FK_62F857DE231F139');
        $this->addSql('ALTER TABLE unifolio_evaluation DROP FOREIGN KEY FK_62F857DEDDEAB1A3');
        $this->addSql('ALTER TABLE unifolio_evaluation DROP FOREIGN KEY FK_62F857DEAA6B2AB6');
        $this->addSql('ALTER TABLE unifolio_evaluation DROP FOREIGN KEY FK_62F857DE37F21E26');
        $this->addSql('ALTER TABLE unifolio_evaluation DROP FOREIGN KEY FK_62F857DE8E780FD');
        $this->addSql('ALTER TABLE unifolio_evaluation DROP FOREIGN KEY FK_62F857DE15761DAB');
        $this->addSql('ALTER TABLE unifolio_evaluation DROP FOREIGN KEY FK_62F857DEB3E9C81');
        $this->addSql('ALTER TABLE unifolio_evaluation DROP FOREIGN KEY FK_62F857DE62399251');
        $this->addSql('DROP TABLE unifolio_campagne');
        $this->addSql('DROP TABLE unifolio_campagne_affectation');
        $this->addSql('DROP TABLE unifolio_commentaire');
        $this->addSql('DROP TABLE unifolio_evaluation');
        $this->addSql('ALTER TABLE unifolio_reponse_critere DROP FOREIGN KEY FK_6A0D1DA7456C5646');
        $this->addSql('DROP INDEX IDX_6A0D1DA7456C5646 ON unifolio_reponse_critere');
        $this->addSql('ALTER TABLE unifolio_reponse_critere DROP evaluation_id');
    }
}
