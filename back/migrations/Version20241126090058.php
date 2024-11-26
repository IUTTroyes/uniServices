<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241126090058 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE structure_annee (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(255) NOT NULL, code_etape VARCHAR(20) DEFAULT NULL, code_version VARCHAR(10) DEFAULT NULL, ordre INT NOT NULL, libelle_long VARCHAR(255) DEFAULT NULL, actif TINYINT(1) NOT NULL, couleur VARCHAR(30) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE structure_pn (id INT AUTO_INCREMENT NOT NULL, diplome_id INT DEFAULT NULL, structure_annee_id INT DEFAULT NULL, libelle VARCHAR(255) NOT NULL, annee_publication INT NOT NULL, INDEX IDX_5EE1408D26F859E2 (diplome_id), INDEX IDX_5EE1408D53B67BA (structure_annee_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE structure_pn ADD CONSTRAINT FK_5EE1408D26F859E2 FOREIGN KEY (diplome_id) REFERENCES structure_diplome (id)');
        $this->addSql('ALTER TABLE structure_pn ADD CONSTRAINT FK_5EE1408D53B67BA FOREIGN KEY (structure_annee_id) REFERENCES structure_annee (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE structure_pn DROP FOREIGN KEY FK_5EE1408D26F859E2');
        $this->addSql('ALTER TABLE structure_pn DROP FOREIGN KEY FK_5EE1408D53B67BA');
        $this->addSql('DROP TABLE structure_annee');
        $this->addSql('DROP TABLE structure_pn');
    }
}
