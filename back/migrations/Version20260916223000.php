<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260916223000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add stage context to preserve legacy Erasmus, foreign and apprenticeship classifications';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE stage_contexte (id INT AUTO_INCREMENT NOT NULL, stage_etudiant_id INT NOT NULL, type VARCHAR(50) NOT NULL, UNIQUE INDEX UNIQ_STAGE_CONTEXTE_ETUDIANT (stage_etudiant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE stage_contexte ADD CONSTRAINT FK_STAGE_CONTEXTE_ETUDIANT FOREIGN KEY (stage_etudiant_id) REFERENCES stage_etudiant (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE stage_contexte');
    }
}
