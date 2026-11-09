<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261106192333 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'UniFolio lot 3 : auto-évaluations et réponses aux critères';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE unifolio_auto_evaluation (id INT AUTO_INCREMENT NOT NULL, reflexion LONGTEXT DEFAULT NULL, statut VARCHAR(20) DEFAULT \'brouillon\' NOT NULL, soumise_le DATETIME(6) DEFAULT NULL, score DOUBLE PRECISION DEFAULT NULL, created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, uuid BINARY(16) NOT NULL, etudiant_id INT NOT NULL, scolarite_id INT NOT NULL, grille_version_id INT NOT NULL, rattachement_id INT DEFAULT NULL, competence_id INT DEFAULT NULL, niveau_id INT DEFAULT NULL, apprentissage_critique_id INT DEFAULT NULL, UNIQUE INDEX uniq_unifolio_auto_evaluation_uuid (uuid), UNIQUE INDEX uniq_unifolio_auto_evaluation_rattachement (rattachement_id), INDEX IDX_E5DEA383DDEAB1A3 (etudiant_id), INDEX IDX_E5DEA383AA6B2AB6 (scolarite_id), INDEX IDX_E5DEA38337F21E26 (grille_version_id), INDEX IDX_E5DEA38315761DAB (competence_id), INDEX IDX_E5DEA383B3E9C81 (niveau_id), INDEX IDX_E5DEA38362399251 (apprentissage_critique_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE unifolio_reponse_critere (id INT AUTO_INCREMENT NOT NULL, valeur_numerique DOUBLE PRECISION DEFAULT NULL, valeur_booleenne TINYINT DEFAULT NULL, texte LONGTEXT DEFAULT NULL, auto_evaluation_id INT DEFAULT NULL, critere_id INT NOT NULL, echelon_id INT DEFAULT NULL, INDEX IDX_6A0D1DA7D9FE854C (auto_evaluation_id), INDEX IDX_6A0D1DA79E5F45AB (critere_id), INDEX IDX_6A0D1DA74D8771C0 (echelon_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE unifolio_auto_evaluation ADD CONSTRAINT FK_E5DEA383DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES etudiant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_auto_evaluation ADD CONSTRAINT FK_E5DEA383AA6B2AB6 FOREIGN KEY (scolarite_id) REFERENCES etudiant_scolarite (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_auto_evaluation ADD CONSTRAINT FK_E5DEA38337F21E26 FOREIGN KEY (grille_version_id) REFERENCES unifolio_grille_version (id)');
        $this->addSql('ALTER TABLE unifolio_auto_evaluation ADD CONSTRAINT FK_E5DEA3838E780FD FOREIGN KEY (rattachement_id) REFERENCES unifolio_trace_rattachement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_auto_evaluation ADD CONSTRAINT FK_E5DEA38315761DAB FOREIGN KEY (competence_id) REFERENCES apc_competence (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_auto_evaluation ADD CONSTRAINT FK_E5DEA383B3E9C81 FOREIGN KEY (niveau_id) REFERENCES apc_niveau (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_auto_evaluation ADD CONSTRAINT FK_E5DEA38362399251 FOREIGN KEY (apprentissage_critique_id) REFERENCES apc_apprentissage_critique (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_reponse_critere ADD CONSTRAINT FK_6A0D1DA7D9FE854C FOREIGN KEY (auto_evaluation_id) REFERENCES unifolio_auto_evaluation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_reponse_critere ADD CONSTRAINT FK_6A0D1DA79E5F45AB FOREIGN KEY (critere_id) REFERENCES unifolio_grille_critere (id)');
        $this->addSql('ALTER TABLE unifolio_reponse_critere ADD CONSTRAINT FK_6A0D1DA74D8771C0 FOREIGN KEY (echelon_id) REFERENCES unifolio_grille_echelon (id)');
        // Exactement une cible : un rattachement de trace, ou un élément du référentiel (bilan).
        $this->addSql('ALTER TABLE unifolio_auto_evaluation ADD CONSTRAINT chk_unifolio_autoeval_cible CHECK ((rattachement_id IS NOT NULL) + (competence_id IS NOT NULL) + (niveau_id IS NOT NULL) + (apprentissage_critique_id IS NOT NULL) = 1)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE unifolio_auto_evaluation DROP CHECK chk_unifolio_autoeval_cible');
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE unifolio_auto_evaluation DROP FOREIGN KEY FK_E5DEA383DDEAB1A3');
        $this->addSql('ALTER TABLE unifolio_auto_evaluation DROP FOREIGN KEY FK_E5DEA383AA6B2AB6');
        $this->addSql('ALTER TABLE unifolio_auto_evaluation DROP FOREIGN KEY FK_E5DEA38337F21E26');
        $this->addSql('ALTER TABLE unifolio_auto_evaluation DROP FOREIGN KEY FK_E5DEA3838E780FD');
        $this->addSql('ALTER TABLE unifolio_auto_evaluation DROP FOREIGN KEY FK_E5DEA38315761DAB');
        $this->addSql('ALTER TABLE unifolio_auto_evaluation DROP FOREIGN KEY FK_E5DEA383B3E9C81');
        $this->addSql('ALTER TABLE unifolio_auto_evaluation DROP FOREIGN KEY FK_E5DEA38362399251');
        $this->addSql('ALTER TABLE unifolio_reponse_critere DROP FOREIGN KEY FK_6A0D1DA7D9FE854C');
        $this->addSql('ALTER TABLE unifolio_reponse_critere DROP FOREIGN KEY FK_6A0D1DA79E5F45AB');
        $this->addSql('ALTER TABLE unifolio_reponse_critere DROP FOREIGN KEY FK_6A0D1DA74D8771C0');
        $this->addSql('DROP TABLE unifolio_auto_evaluation');
        $this->addSql('DROP TABLE unifolio_reponse_critere');
    }
}
