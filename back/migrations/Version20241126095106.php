<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241126095106 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE structure_semestre (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(255) NOT NULL, ordre_annee INT NOT NULL, ordre_lmd INT NOT NULL, actif TINYINT(1) NOT NULL, nb_groupes_cm INT NOT NULL, nb_groupes_td INT NOT NULL, nb_groupes_tp VARCHAR(255) NOT NULL, code_element VARCHAR(20) DEFAULT NULL, opt JSON NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE structure_annee ADD opt JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE structure_semestre');
        $this->addSql('ALTER TABLE structure_annee DROP opt');
    }
}
