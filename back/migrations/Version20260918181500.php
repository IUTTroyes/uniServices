<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260918181500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Preserve V3 HRS comments';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE personnel_enseignant_hrs ADD commentaire LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE personnel_enseignant_hrs DROP commentaire');
    }
}
