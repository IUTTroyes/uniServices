<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260708151446 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE email_template (id INT AUTO_INCREMENT NOT NULL, departement_id INT DEFAULT NULL, email_key VARCHAR(100) NOT NULL, locale VARCHAR(5) NOT NULL, subject VARCHAR(255) NOT NULL, body_html LONGTEXT NOT NULL, body_text LONGTEXT DEFAULT NULL, use_layout TINYINT(1) NOT NULL, created DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated DATETIME(6) DEFAULT NULL, INDEX IDX_9C0600CACCF9E01E (departement_id), UNIQUE INDEX uq_email_template (email_key, departement_id, locale), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE email_template ADD CONSTRAINT FK_9C0600CACCF9E01E FOREIGN KEY (departement_id) REFERENCES structure_departement (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE email_template DROP FOREIGN KEY FK_9C0600CACCF9E01E');
        $this->addSql('DROP TABLE email_template');
    }
}
