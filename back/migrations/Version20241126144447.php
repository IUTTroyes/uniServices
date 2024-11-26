<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241126144447 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE structure_diplome ADD departement_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE structure_diplome ADD CONSTRAINT FK_30EE3D7FCCF9E01E FOREIGN KEY (departement_id) REFERENCES structure_departement (id)');
        $this->addSql('CREATE INDEX IDX_30EE3D7FCCF9E01E ON structure_diplome (departement_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE structure_diplome DROP FOREIGN KEY FK_30EE3D7FCCF9E01E');
        $this->addSql('DROP INDEX IDX_30EE3D7FCCF9E01E ON structure_diplome');
        $this->addSql('ALTER TABLE structure_diplome DROP departement_id');
    }
}
