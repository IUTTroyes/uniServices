<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241125181206 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE departement (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(255) NOT NULL, logo_name VARCHAR(255) DEFAULT NULL, tel_contact VARCHAR(16) DEFAULT NULL, couleur VARCHAR(16) DEFAULT NULL, site_web VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, actif TINYINT(1) NOT NULL, opt JSON NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE etudiant CHANGE photo_name photo_name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE personnel CHANGE photo_name photo_name VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE departement');
        $this->addSql('ALTER TABLE etudiant CHANGE photo_name photo_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE personnel CHANGE photo_name photo_name VARCHAR(255) DEFAULT NULL');
    }
}
