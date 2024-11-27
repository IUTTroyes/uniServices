<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241127093154 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE structure_groupe DROP FOREIGN KEY FK_1D006B0B727ACA70');
        $this->addSql('ALTER TABLE structure_groupe ADD CONSTRAINT FK_1D006B0B727ACA70 FOREIGN KEY (parent_id) REFERENCES structure_groupe (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE structure_groupe DROP FOREIGN KEY FK_1D006B0B727ACA70');
        $this->addSql('ALTER TABLE structure_groupe ADD CONSTRAINT FK_1D006B0B727ACA70 FOREIGN KEY (parent_id) REFERENCES structure_groupe (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
