<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260918135500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Preserve intranet V3 original document category flag';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document_category ADD is_original TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document_category DROP is_original');
    }
}
