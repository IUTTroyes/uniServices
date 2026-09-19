<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260918114500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add legacy identifiers to document and document_category for intranet V3 migration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document ADD old_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_DOCUMENT_OLD_ID ON document (old_id)');
        $this->addSql('ALTER TABLE document_category ADD old_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_DOCUMENT_CATEGORY_OLD_ID ON document_category (old_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_DOCUMENT_OLD_ID ON document');
        $this->addSql('ALTER TABLE document DROP old_id');
        $this->addSql('DROP INDEX IDX_DOCUMENT_CATEGORY_OLD_ID ON document_category');
        $this->addSql('ALTER TABLE document_category DROP old_id');
    }
}
