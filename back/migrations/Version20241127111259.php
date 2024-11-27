<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241127111259 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE structure_diplome DROP FOREIGN KEY FK_30EE3D7F727ACA70');
        $this->addSql('ALTER TABLE structure_diplome ADD CONSTRAINT FK_30EE3D7F727ACA70 FOREIGN KEY (parent_id) REFERENCES structure_diplome (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE structure_groupe DROP FOREIGN KEY FK_1D006B0BA586286C');
        $this->addSql('DROP INDEX IDX_1D006B0BA586286C ON structure_groupe');
        $this->addSql('ALTER TABLE structure_groupe DROP enfants_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE structure_groupe ADD enfants_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE structure_groupe ADD CONSTRAINT FK_1D006B0BA586286C FOREIGN KEY (enfants_id) REFERENCES structure_groupe (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_1D006B0BA586286C ON structure_groupe (enfants_id)');
        $this->addSql('ALTER TABLE structure_diplome DROP FOREIGN KEY FK_30EE3D7F727ACA70');
        $this->addSql('ALTER TABLE structure_diplome ADD CONSTRAINT FK_30EE3D7F727ACA70 FOREIGN KEY (parent_id) REFERENCES structure_diplome (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
