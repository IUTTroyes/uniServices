<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261107211227 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(80) NOT NULL, source VARCHAR(40) NOT NULL, texte VARCHAR(500) NOT NULL, url VARCHAR(255) DEFAULT NULL, nombre INT DEFAULT 1 NOT NULL, cree_le DATETIME(6) NOT NULL, mis_ajour_le DATETIME(6) NOT NULL, lue_le DATETIME(6) DEFAULT NULL, email_traite_le DATETIME(6) DEFAULT NULL, uuid BINARY(16) NOT NULL, etudiant_id INT DEFAULT NULL, personnel_id INT DEFAULT NULL, INDEX idx_notification_etudiant_lue (etudiant_id, lue_le), INDEX idx_notification_personnel_lue (personnel_id, lue_le), INDEX idx_notification_email (email_traite_le), UNIQUE INDEX uniq_notification_uuid (uuid), INDEX IDX_BF5476CADDEAB1A3 (etudiant_id), INDEX IDX_BF5476CA1C109075 (personnel_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE notification_preference (id INT AUTO_INCREMENT NOT NULL, actif TINYINT DEFAULT 1 NOT NULL, frequence VARCHAR(20) DEFAULT \'quotidien\' NOT NULL, reglages JSON NOT NULL, dernier_resume_le DATETIME(6) DEFAULT NULL, etudiant_id INT DEFAULT NULL, personnel_id INT DEFAULT NULL, UNIQUE INDEX uniq_notification_pref_etudiant (etudiant_id), UNIQUE INDEX uniq_notification_pref_personnel (personnel_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CADDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES etudiant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA1C109075 FOREIGN KEY (personnel_id) REFERENCES personnel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification_preference ADD CONSTRAINT FK_A61B1571DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES etudiant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification_preference ADD CONSTRAINT FK_A61B15711C109075 FOREIGN KEY (personnel_id) REFERENCES personnel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_notification_attente DROP FOREIGN KEY `FK_F9B878911C109075`');
        $this->addSql('ALTER TABLE unifolio_notification_attente DROP FOREIGN KEY `FK_F9B87891DDEAB1A3`');
        $this->addSql('ALTER TABLE unifolio_preference_notification DROP FOREIGN KEY `FK_2B4C93EE1C109075`');
        $this->addSql('ALTER TABLE unifolio_preference_notification DROP FOREIGN KEY `FK_2B4C93EEDDEAB1A3`');
        $this->addSql('DROP TABLE unifolio_notification_attente');
        $this->addSql('DROP TABLE unifolio_preference_notification');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE unifolio_notification_attente (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(40) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, texte VARCHAR(500) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, url VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, nombre INT DEFAULT 1 NOT NULL, creee_le DATETIME(6) NOT NULL, envoyee_le DATETIME(6) DEFAULT NULL, etudiant_id INT DEFAULT NULL, personnel_id INT DEFAULT NULL, INDEX IDX_F9B878911C109075 (personnel_id), INDEX IDX_F9B87891DDEAB1A3 (etudiant_id), INDEX idx_unifolio_notif_attente_envoi (envoyee_le), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE unifolio_preference_notification (id INT AUTO_INCREMENT NOT NULL, actif TINYINT DEFAULT 1 NOT NULL, frequence VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT \'quotidien\' NOT NULL COLLATE `utf8mb4_unicode_ci`, reglages JSON NOT NULL, dernier_resume_le DATETIME(6) DEFAULT NULL, etudiant_id INT DEFAULT NULL, personnel_id INT DEFAULT NULL, UNIQUE INDEX uniq_unifolio_pref_notif_etudiant (etudiant_id), UNIQUE INDEX uniq_unifolio_pref_notif_personnel (personnel_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE unifolio_notification_attente ADD CONSTRAINT `FK_F9B878911C109075` FOREIGN KEY (personnel_id) REFERENCES personnel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_notification_attente ADD CONSTRAINT `FK_F9B87891DDEAB1A3` FOREIGN KEY (etudiant_id) REFERENCES etudiant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_preference_notification ADD CONSTRAINT `FK_2B4C93EE1C109075` FOREIGN KEY (personnel_id) REFERENCES personnel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_preference_notification ADD CONSTRAINT `FK_2B4C93EEDDEAB1A3` FOREIGN KEY (etudiant_id) REFERENCES etudiant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CADDEAB1A3');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA1C109075');
        $this->addSql('ALTER TABLE notification_preference DROP FOREIGN KEY FK_A61B1571DDEAB1A3');
        $this->addSql('ALTER TABLE notification_preference DROP FOREIGN KEY FK_A61B15711C109075');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE notification_preference');
    }
}
