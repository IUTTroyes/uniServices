<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241125185204 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE structure_diplome (id INT AUTO_INCREMENT NOT NULL, responsable_diplome_id INT DEFAULT NULL, assistant_diplome_id INT DEFAULT NULL, parent_id INT DEFAULT NULL, libelle VARCHAR(255) NOT NULL, volume_horaire INT NOT NULL, code_celcat_departement INT NOT NULL, sigle VARCHAR(40) DEFAULT NULL, actif TINYINT(1) NOT NULL, logo_partenaire VARCHAR(255) DEFAULT NULL, opt JSON NOT NULL, INDEX IDX_30EE3D7FD2D1AAE2 (responsable_diplome_id), INDEX IDX_30EE3D7F39A24FD8 (assistant_diplome_id), INDEX IDX_30EE3D7F727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE structure_etudiant (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(75) NOT NULL, mail_univ VARCHAR(255) NOT NULL, password VARCHAR(255) DEFAULT NULL, roles JSON NOT NULL, prenom VARCHAR(75) NOT NULL, nom VARCHAR(75) NOT NULL, photo_name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE structure_personnel (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(75) NOT NULL, mail_univ VARCHAR(255) NOT NULL, password VARCHAR(255) DEFAULT NULL, roles JSON NOT NULL, prenom VARCHAR(75) NOT NULL, nom VARCHAR(75) NOT NULL, photo_name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE structure_diplome ADD CONSTRAINT FK_30EE3D7FD2D1AAE2 FOREIGN KEY (responsable_diplome_id) REFERENCES structure_personnel (id)');
        $this->addSql('ALTER TABLE structure_diplome ADD CONSTRAINT FK_30EE3D7F39A24FD8 FOREIGN KEY (assistant_diplome_id) REFERENCES structure_personnel (id)');
        $this->addSql('ALTER TABLE structure_diplome ADD CONSTRAINT FK_30EE3D7F727ACA70 FOREIGN KEY (parent_id) REFERENCES structure_diplome (id)');
        $this->addSql('ALTER TABLE diplome DROP FOREIGN KEY FK_EB4C4D4E39A24FD8');
        $this->addSql('ALTER TABLE diplome DROP FOREIGN KEY FK_EB4C4D4E727ACA70');
        $this->addSql('ALTER TABLE diplome DROP FOREIGN KEY FK_EB4C4D4ED2D1AAE2');
        $this->addSql('DROP TABLE diplome');
        $this->addSql('DROP TABLE etudiant');
        $this->addSql('DROP TABLE personnel');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE diplome (id INT AUTO_INCREMENT NOT NULL, responsable_diplome_id INT DEFAULT NULL, assistant_diplome_id INT DEFAULT NULL, parent_id INT DEFAULT NULL, libelle VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, volume_horaire INT NOT NULL, code_celcat_departement INT NOT NULL, sigle VARCHAR(40) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, actif TINYINT(1) NOT NULL, logo_partenaire VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, opt JSON NOT NULL, INDEX IDX_EB4C4D4E39A24FD8 (assistant_diplome_id), INDEX IDX_EB4C4D4E727ACA70 (parent_id), INDEX IDX_EB4C4D4ED2D1AAE2 (responsable_diplome_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE etudiant (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(75) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, mail_univ VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, password VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, roles JSON NOT NULL, prenom VARCHAR(75) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, nom VARCHAR(75) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, photo_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE personnel (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(75) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, mail_univ VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, password VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, roles JSON NOT NULL, prenom VARCHAR(75) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, nom VARCHAR(75) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, photo_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE diplome ADD CONSTRAINT FK_EB4C4D4E39A24FD8 FOREIGN KEY (assistant_diplome_id) REFERENCES personnel (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE diplome ADD CONSTRAINT FK_EB4C4D4E727ACA70 FOREIGN KEY (parent_id) REFERENCES diplome (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE diplome ADD CONSTRAINT FK_EB4C4D4ED2D1AAE2 FOREIGN KEY (responsable_diplome_id) REFERENCES personnel (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE structure_diplome DROP FOREIGN KEY FK_30EE3D7FD2D1AAE2');
        $this->addSql('ALTER TABLE structure_diplome DROP FOREIGN KEY FK_30EE3D7F39A24FD8');
        $this->addSql('ALTER TABLE structure_diplome DROP FOREIGN KEY FK_30EE3D7F727ACA70');
        $this->addSql('DROP TABLE structure_diplome');
        $this->addSql('DROP TABLE structure_etudiant');
        $this->addSql('DROP TABLE structure_personnel');
    }
}
