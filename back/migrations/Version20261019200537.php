<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261019200537 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE questionnaire ADD completion_notified TINYINT DEFAULT 0 NOT NULL, ADD completion_notified_at DATETIME(6) DEFAULT NULL, ADD author_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE questionnaire ADD CONSTRAINT FK_7A64DAFF675F31B FOREIGN KEY (author_id) REFERENCES personnel (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_7A64DAFF675F31B ON questionnaire (author_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE questionnaire DROP FOREIGN KEY FK_7A64DAFF675F31B');
        $this->addSql('DROP INDEX IDX_7A64DAFF675F31B ON questionnaire');
        $this->addSql('ALTER TABLE questionnaire DROP completion_notified, DROP completion_notified_at, DROP author_id');
    }
}
