<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241126102330 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE structure_groupe (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, enfants_id INT DEFAULT NULL, libelle VARCHAR(255) NOT NULL, code_apogee VARCHAR(50) NOT NULL, ordre INT DEFAULT NULL, INDEX IDX_1D006B0B727ACA70 (parent_id), INDEX IDX_1D006B0BA586286C (enfants_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE structure_groupe_structure_etudiant (structure_groupe_id INT NOT NULL, structure_etudiant_id INT NOT NULL, INDEX IDX_DF3B9BB2EC1D98A0 (structure_groupe_id), INDEX IDX_DF3B9BB24CBC9300 (structure_etudiant_id), PRIMARY KEY(structure_groupe_id, structure_etudiant_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE structure_groupe_structure_semestre (structure_groupe_id INT NOT NULL, structure_semestre_id INT NOT NULL, INDEX IDX_DF2D36EDEC1D98A0 (structure_groupe_id), INDEX IDX_DF2D36EDC4218D78 (structure_semestre_id), PRIMARY KEY(structure_groupe_id, structure_semestre_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE structure_groupe ADD CONSTRAINT FK_1D006B0B727ACA70 FOREIGN KEY (parent_id) REFERENCES structure_groupe (id)');
        $this->addSql('ALTER TABLE structure_groupe ADD CONSTRAINT FK_1D006B0BA586286C FOREIGN KEY (enfants_id) REFERENCES structure_groupe (id)');
        $this->addSql('ALTER TABLE structure_groupe_structure_etudiant ADD CONSTRAINT FK_DF3B9BB2EC1D98A0 FOREIGN KEY (structure_groupe_id) REFERENCES structure_groupe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE structure_groupe_structure_etudiant ADD CONSTRAINT FK_DF3B9BB24CBC9300 FOREIGN KEY (structure_etudiant_id) REFERENCES structure_etudiant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE structure_groupe_structure_semestre ADD CONSTRAINT FK_DF2D36EDEC1D98A0 FOREIGN KEY (structure_groupe_id) REFERENCES structure_groupe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE structure_groupe_structure_semestre ADD CONSTRAINT FK_DF2D36EDC4218D78 FOREIGN KEY (structure_semestre_id) REFERENCES structure_semestre (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE structure_groupe DROP FOREIGN KEY FK_1D006B0B727ACA70');
        $this->addSql('ALTER TABLE structure_groupe DROP FOREIGN KEY FK_1D006B0BA586286C');
        $this->addSql('ALTER TABLE structure_groupe_structure_etudiant DROP FOREIGN KEY FK_DF3B9BB2EC1D98A0');
        $this->addSql('ALTER TABLE structure_groupe_structure_etudiant DROP FOREIGN KEY FK_DF3B9BB24CBC9300');
        $this->addSql('ALTER TABLE structure_groupe_structure_semestre DROP FOREIGN KEY FK_DF2D36EDEC1D98A0');
        $this->addSql('ALTER TABLE structure_groupe_structure_semestre DROP FOREIGN KEY FK_DF2D36EDC4218D78');
        $this->addSql('DROP TABLE structure_groupe');
        $this->addSql('DROP TABLE structure_groupe_structure_etudiant');
        $this->addSql('DROP TABLE structure_groupe_structure_semestre');
    }
}
