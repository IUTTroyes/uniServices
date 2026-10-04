<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260916214500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds the intranet V3 legacy identifier used to migrate stage periods.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stage_periode ADD old_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_STAGE_PERIODE_OLD_ID ON stage_periode (old_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_STAGE_PERIODE_OLD_ID ON stage_periode');
        $this->addSql('ALTER TABLE stage_periode DROP old_id');
    }
}
