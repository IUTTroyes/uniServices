<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261124140000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE parcauto_valeur_referentiel (groupe VARCHAR(20) NOT NULL, code VARCHAR(30) NOT NULL, libelle VARCHAR(120) NOT NULL, protegee TINYINT(1) NOT NULL, PRIMARY KEY(groupe, code)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql("INSERT INTO parcauto_valeur_referentiel (groupe, code, libelle, protegee) VALUES
            ('energie', 'essence', 'Essence', 0),
            ('energie', 'diesel', 'Diesel', 0),
            ('energie', 'hybride', 'Hybride', 0),
            ('energie', 'electrique', 'Électrique', 0),
            ('energie', 'autre', 'Autre', 0),
            ('equipement', 'prestataire', 'Prestataire', 1),
            ('equipement', 'carte', 'Carte carburant', 1),
            ('equipement', 'borne', 'Borne URCA', 1),
            ('plan', 'controle_technique', 'Contrôle technique', 1),
            ('plan', 'revision', 'Révision', 0),
            ('plan', 'pneus', 'Contrôle des pneus', 1),
            ('plan', 'entretien', 'Entretien constructeur', 0),
            ('journal', 'carburant', 'Plein de carburant', 1),
            ('journal', 'facture', 'Facture', 1),
            ('journal', 'pneus', 'Mesure d’un train de pneus', 1),
            ('journal', 'remplacement_pneus', 'Retrait d’un train de pneus', 1),
            ('journal', 'incident', 'Incident mécanique', 1),
            ('journal', 'remise_service', 'Remise en service', 1),
            ('journal', 'entretien', 'Entretien', 1)");
        $this->addSql("INSERT IGNORE INTO parcauto_valeur_referentiel SELECT DISTINCT 'energie', energie, energie, 0 FROM parcauto_vehicule");
        $this->addSql("INSERT IGNORE INTO parcauto_valeur_referentiel SELECT DISTINCT 'equipement', type, type, 0 FROM parcauto_equipement");
        $this->addSql("INSERT IGNORE INTO parcauto_valeur_referentiel SELECT DISTINCT 'plan', type, type, 0 FROM parcauto_echeance_entretien");
        $this->addSql("INSERT IGNORE INTO parcauto_valeur_referentiel SELECT DISTINCT 'journal', type, type, 0 FROM parcauto_journal_vehicule");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE parcauto_valeur_referentiel');
    }
}
