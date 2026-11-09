<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Signataire des conventions de stage porté par l'établissement (jusqu'ici écrit en dur dans le PDF).
 */
final class Version20261107214544 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Établissement : signataire des conventions de stage (nom et qualité)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE etablissement ADD signataire_nom VARCHAR(150) DEFAULT NULL, ADD signataire_qualite VARCHAR(255) DEFAULT NULL');
        // Reprise des valeurs jusqu'ici écrites en dur dans la convention, pour que les PDF restent identiques.
        $this->addSql("UPDATE etablissement SET signataire_nom = 'Martial Martin', signataire_qualite = 'Directeur' WHERE is_main = 1 AND signataire_nom IS NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE etablissement DROP signataire_nom, DROP signataire_qualite');
    }
}
