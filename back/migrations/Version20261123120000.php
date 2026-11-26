<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261123120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Garantit l’unicité étudiant–année universitaire de la scolarité annuelle.';
    }

    public function up(Schema $schema): void
    {
        // Important : une base issue d'un ancien transfert contenant déjà des
        // doublons doit être consolidée / réinitialisée AVANT cette migration.
        // Aucun DELETE automatique : les semestres, notes et groupes associés
        // pourraient être perdus.
        $duplicates = (int) $this->connection->fetchOne(<<<'SQL'
SELECT COUNT(*) FROM (
    SELECT etudiant_id, annee_universitaire_id
    FROM etudiant_scolarite
    GROUP BY etudiant_id, annee_universitaire_id
    HAVING COUNT(*) > 1
) doublons
SQL);
        $this->abortIf($duplicates > 0, sprintf(
            '%d couple(s) étudiant–année en double : consolider les dossiers ou réinitialiser la base de recette avant d’ajouter la contrainte.',
            $duplicates
        ));

        $this->addSql('CREATE UNIQUE INDEX UNIQ_SCOLARITE_ETUDIANT_ANNEE ON etudiant_scolarite (etudiant_id, annee_universitaire_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_SCOLARITE_ETUDIANT_ANNEE ON etudiant_scolarite');
    }
}
