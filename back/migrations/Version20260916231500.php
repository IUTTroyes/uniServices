<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260916231500 extends AbstractMigration
{
    public function getDescription(): string { return 'Add legacy IDs to Stage companies and contacts'; }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE entreprise ADD old_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_ENTREPRISE_OLD_ID ON entreprise (old_id)');
        $this->addSql('ALTER TABLE contact ADD old_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_CONTACT_OLD_ID ON contact (old_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_ENTREPRISE_OLD_ID ON entreprise');
        $this->addSql('ALTER TABLE entreprise DROP old_id');
        $this->addSql('DROP INDEX IDX_CONTACT_OLD_ID ON contact');
        $this->addSql('ALTER TABLE contact DROP old_id');
    }
}
