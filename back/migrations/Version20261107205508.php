<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261107205508 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE unifolio_notification_attente (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(40) NOT NULL, texte VARCHAR(500) NOT NULL, url VARCHAR(255) DEFAULT NULL, nombre INT DEFAULT 1 NOT NULL, creee_le DATETIME(6) NOT NULL, envoyee_le DATETIME(6) DEFAULT NULL, etudiant_id INT DEFAULT NULL, personnel_id INT DEFAULT NULL, INDEX idx_unifolio_notif_attente_envoi (envoyee_le), INDEX IDX_F9B87891DDEAB1A3 (etudiant_id), INDEX IDX_F9B878911C109075 (personnel_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE unifolio_preference_notification (id INT AUTO_INCREMENT NOT NULL, actif TINYINT DEFAULT 1 NOT NULL, frequence VARCHAR(20) DEFAULT \'quotidien\' NOT NULL, reglages JSON NOT NULL, dernier_resume_le DATETIME(6) DEFAULT NULL, etudiant_id INT DEFAULT NULL, personnel_id INT DEFAULT NULL, UNIQUE INDEX uniq_unifolio_pref_notif_etudiant (etudiant_id), UNIQUE INDEX uniq_unifolio_pref_notif_personnel (personnel_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE unifolio_notification_attente ADD CONSTRAINT FK_F9B87891DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES etudiant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_notification_attente ADD CONSTRAINT FK_F9B878911C109075 FOREIGN KEY (personnel_id) REFERENCES personnel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_preference_notification ADD CONSTRAINT FK_2B4C93EEDDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES etudiant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_preference_notification ADD CONSTRAINT FK_2B4C93EE1C109075 FOREIGN KEY (personnel_id) REFERENCES personnel (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE unifolio_notification_attente DROP FOREIGN KEY FK_F9B87891DDEAB1A3');
        $this->addSql('ALTER TABLE unifolio_notification_attente DROP FOREIGN KEY FK_F9B878911C109075');
        $this->addSql('ALTER TABLE unifolio_preference_notification DROP FOREIGN KEY FK_2B4C93EEDDEAB1A3');
        $this->addSql('ALTER TABLE unifolio_preference_notification DROP FOREIGN KEY FK_2B4C93EE1C109075');
        $this->addSql('DROP TABLE unifolio_notification_attente');
        $this->addSql('DROP TABLE unifolio_preference_notification');
    }
}
