<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261109111510 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE finance_taux_tva (id INT AUTO_INCREMENT NOT NULL, taux NUMERIC(5, 2) NOT NULL, libelle VARCHAR(100) NOT NULL, actif TINYINT DEFAULT 1 NOT NULL, par_defaut TINYINT DEFAULT 0 NOT NULL, ordre INT DEFAULT 0 NOT NULL, UNIQUE INDEX UNIQ_A0A495F5809A3D7D (taux), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE finance_bon_commande ADD taux_tva NUMERIC(5, 2) DEFAULT NULL, ADD fournisseur_propose VARCHAR(255) DEFAULT NULL, ADD motif_rejet LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE finance_taux_tva');
        $this->addSql('ALTER TABLE finance_bon_commande DROP taux_tva, DROP fournisseur_propose, DROP motif_rejet');
    }
}
