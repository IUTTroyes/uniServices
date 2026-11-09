<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261107081011 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE unifolio_portfolio_publication (id INT AUTO_INCREMENT NOT NULL, numero INT NOT NULL, statut VARCHAR(20) NOT NULL, active TINYINT DEFAULT 0 NOT NULL, instantane JSON NOT NULL, fichiers JSON NOT NULL, erreur LONGTEXT DEFAULT NULL, genere_le DATETIME(6) DEFAULT NULL, desactivee_le DATETIME(6) DEFAULT NULL, motif_moderation LONGTEXT DEFAULT NULL, created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, uuid BINARY(16) NOT NULL, portfolio_id INT NOT NULL, INDEX idx_unifolio_publication_active (portfolio_id, active), UNIQUE INDEX uniq_unifolio_publication_uuid (uuid), UNIQUE INDEX uniq_unifolio_publication_numero (portfolio_id, numero), INDEX IDX_21487EEFB96B5643 (portfolio_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE unifolio_portfolio_publication ADD CONSTRAINT FK_21487EEFB96B5643 FOREIGN KEY (portfolio_id) REFERENCES unifolio_portfolio (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unifolio_portfolio ADD slug VARCHAR(60) DEFAULT NULL, ADD token_partage VARCHAR(64) DEFAULT NULL, ADD indexable TINYINT DEFAULT 0 NOT NULL, ADD description VARCHAR(300) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_unifolio_portfolio_slug ON unifolio_portfolio (slug)');
        $this->addSql('CREATE UNIQUE INDEX uniq_unifolio_portfolio_token ON unifolio_portfolio (token_partage)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE unifolio_portfolio_publication DROP FOREIGN KEY FK_21487EEFB96B5643');
        $this->addSql('DROP TABLE unifolio_portfolio_publication');
        $this->addSql('DROP INDEX uniq_unifolio_portfolio_slug ON unifolio_portfolio');
        $this->addSql('DROP INDEX uniq_unifolio_portfolio_token ON unifolio_portfolio');
        $this->addSql('ALTER TABLE unifolio_portfolio DROP slug, DROP token_partage, DROP indexable, DROP description');
    }
}
