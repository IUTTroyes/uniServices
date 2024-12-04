<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241204144155 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE etudiant_absence (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE apc_referentiel DROP FOREIGN KEY FK_E744CC813BFB8FC7');
        $this->addSql('DROP INDEX IDX_E744CC813BFB8FC7 ON apc_referentiel');
        $this->addSql('ALTER TABLE apc_referentiel DROP type_diplome_id');
        $this->addSql('ALTER TABLE scol_enseignement ADD heures JSON NOT NULL, DROP heures_ppn, DROP heures_formation, DROP code_element');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE etudiant_absence');
        $this->addSql('ALTER TABLE apc_referentiel ADD type_diplome_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE apc_referentiel ADD CONSTRAINT FK_E744CC813BFB8FC7 FOREIGN KEY (type_diplome_id) REFERENCES structure_type_diplome (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_E744CC813BFB8FC7 ON apc_referentiel (type_diplome_id)');
        $this->addSql('ALTER TABLE scol_enseignement ADD heures_formation JSON NOT NULL, ADD code_element VARCHAR(20) DEFAULT NULL, CHANGE heures heures_ppn JSON NOT NULL');
    }
}
