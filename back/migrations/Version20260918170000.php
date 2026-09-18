<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260918170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add updated_at to existing timestamped entities migrated to TimestampableInterface';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE document_category ADD updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addSql('UPDATE document_category SET updated_at = created_at WHERE updated_at IS NULL');
        $this->addSql("ALTER TABLE document_category MODIFY updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'");

        $this->addSql("ALTER TABLE questionnaire_invitation ADD updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addSql('UPDATE questionnaire_invitation SET updated_at = created_at WHERE updated_at IS NULL');
        $this->addSql("ALTER TABLE questionnaire_invitation MODIFY updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document_category DROP updated_at');
        $this->addSql('ALTER TABLE questionnaire_invitation DROP updated_at');
    }
}
