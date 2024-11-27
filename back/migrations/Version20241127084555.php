<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241127084555 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE structure_groupe ADD type VARCHAR(10) NOT NULL');
        $this->addSql('ALTER TABLE structure_semestre ADD annee_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE structure_semestre ADD CONSTRAINT FK_206D2DF6543EC5F0 FOREIGN KEY (annee_id) REFERENCES structure_annee (id)');
        $this->addSql('CREATE INDEX IDX_206D2DF6543EC5F0 ON structure_semestre (annee_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE structure_groupe DROP type');
        $this->addSql('ALTER TABLE structure_semestre DROP FOREIGN KEY FK_206D2DF6543EC5F0');
        $this->addSql('DROP INDEX IDX_206D2DF6543EC5F0 ON structure_semestre');
        $this->addSql('ALTER TABLE structure_semestre DROP annee_id');
    }
}
