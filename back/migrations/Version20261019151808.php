<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261019151808 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE questionnaire ADD departement_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE questionnaire ADD CONSTRAINT FK_7A64DAFCCF9E01E FOREIGN KEY (departement_id) REFERENCES structure_departement (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_7A64DAFCCF9E01E ON questionnaire (departement_id)');
        $this->addSql('ALTER TABLE questionnaire_invitation ADD reminders_history JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE questionnaire DROP FOREIGN KEY FK_7A64DAFCCF9E01E');
        $this->addSql('DROP INDEX IDX_7A64DAFCCF9E01E ON questionnaire');
        $this->addSql('ALTER TABLE questionnaire DROP departement_id');
        $this->addSql('ALTER TABLE questionnaire_invitation DROP reminders_history');
    }
}
