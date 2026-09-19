<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260911112500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store rattrapage start and end values as times instead of dates.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE scol_evaluation_rattrapage CHANGE heure_debut heure_debut TIME DEFAULT NULL, CHANGE heure_fin heure_fin TIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE scol_evaluation_rattrapage CHANGE heure_debut heure_debut DATE DEFAULT NULL, CHANGE heure_fin heure_fin DATE DEFAULT NULL');
    }
}
