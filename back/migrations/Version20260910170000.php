<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260910170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add temporary intranet V3 legacy ids to structure_pn and structure_annee.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE structure_pn ADD old_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE structure_annee ADD old_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_STRUCTURE_PN_OLD_ID ON structure_pn (old_id)');
        $this->addSql('CREATE INDEX IDX_STRUCTURE_ANNEE_OLD_ID ON structure_annee (old_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_STRUCTURE_PN_OLD_ID ON structure_pn');
        $this->addSql('DROP INDEX IDX_STRUCTURE_ANNEE_OLD_ID ON structure_annee');
        $this->addSql('ALTER TABLE structure_pn DROP old_id');
        $this->addSql('ALTER TABLE structure_annee DROP old_id');
    }
}
