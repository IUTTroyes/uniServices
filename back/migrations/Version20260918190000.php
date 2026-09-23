<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260918190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Preserve V3 student administrative fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE etudiant ADD demandeur_emploi TINYINT(1) DEFAULT 0 NOT NULL, ADD login_specifique VARCHAR(50) DEFAULT NULL, ADD formation_continue TINYINT(1) DEFAULT 0 NOT NULL, ADD intitule_securite_sociale VARCHAR(255) DEFAULT NULL, ADD adresse_securite_sociale VARCHAR(255) DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE etudiant DROP demandeur_emploi, DROP login_specifique, DROP formation_continue, DROP intitule_securite_sociale, DROP adresse_securite_sociale');
    }
}
