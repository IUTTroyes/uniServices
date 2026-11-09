<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * L'établissement est l'IUT ; son université de rattachement est embarquée (colonnes universite_*).
 * Reprise : si le libellé de l'établissement principal est celui d'une université, il passe (avec son adresse et son
 * téléphone) dans les colonnes université, et l'établissement prend le libellé de l'IUT.
 */
final class Version20261107215443 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Établissement : directeur et université de rattachement (libellé, adresse, téléphone, président)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE etablissement ADD directeur_nom VARCHAR(150) DEFAULT NULL, ADD universite_libelle VARCHAR(255) DEFAULT NULL, ADD universite_adresse JSON DEFAULT NULL, ADD universite_telephone VARCHAR(20) DEFAULT NULL, ADD universite_president VARCHAR(150) DEFAULT NULL');

        $this->addSql(<<<'SQL'
            UPDATE etablissement
            SET universite_libelle = libelle,
                universite_adresse = adresse,
                universite_telephone = telephone,
                libelle = 'IUT de Troyes',
                adresse = NULL,
                telephone = NULL
            WHERE is_main = 1 AND universite_libelle IS NULL AND libelle LIKE 'Universit%'
            SQL);
        // Le signataire actuel des conventions est le directeur de l'IUT.
        $this->addSql('UPDATE etablissement SET directeur_nom = signataire_nom WHERE is_main = 1 AND directeur_nom IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE etablissement
            SET libelle = universite_libelle,
                adresse = COALESCE(adresse, universite_adresse),
                telephone = COALESCE(telephone, universite_telephone)
            WHERE is_main = 1 AND universite_libelle IS NOT NULL
            SQL);
        $this->addSql('ALTER TABLE etablissement DROP directeur_nom, DROP universite_libelle, DROP universite_adresse, DROP universite_telephone, DROP universite_president');
    }
}
