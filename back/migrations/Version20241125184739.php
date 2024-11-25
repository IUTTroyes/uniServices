<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241125184739 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE diplome (id INT AUTO_INCREMENT NOT NULL, responsable_diplome_id INT DEFAULT NULL, assistant_diplome_id INT DEFAULT NULL, parent_id INT DEFAULT NULL, libelle VARCHAR(255) NOT NULL, volume_horaire INT NOT NULL, code_celcat_departement INT NOT NULL, sigle VARCHAR(40) DEFAULT NULL, actif TINYINT(1) NOT NULL, logo_partenaire VARCHAR(255) DEFAULT NULL, opt JSON NOT NULL, INDEX IDX_EB4C4D4ED2D1AAE2 (responsable_diplome_id), INDEX IDX_EB4C4D4E39A24FD8 (assistant_diplome_id), INDEX IDX_EB4C4D4E727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE diplome ADD CONSTRAINT FK_EB4C4D4ED2D1AAE2 FOREIGN KEY (responsable_diplome_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE diplome ADD CONSTRAINT FK_EB4C4D4E39A24FD8 FOREIGN KEY (assistant_diplome_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE diplome ADD CONSTRAINT FK_EB4C4D4E727ACA70 FOREIGN KEY (parent_id) REFERENCES diplome (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE diplome DROP FOREIGN KEY FK_EB4C4D4ED2D1AAE2');
        $this->addSql('ALTER TABLE diplome DROP FOREIGN KEY FK_EB4C4D4E39A24FD8');
        $this->addSql('ALTER TABLE diplome DROP FOREIGN KEY FK_EB4C4D4E727ACA70');
        $this->addSql('DROP TABLE diplome');
    }
}
