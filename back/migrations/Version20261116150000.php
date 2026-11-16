<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Service\Calendrier\CalendrierUniversitaireService;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261116150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Calcule le calendrier depuis un lundi de référence annuel et reprend les contraintes par date.';
    }

    public function isTransactional(): bool { return false; }

    public function up(Schema $schema): void
    {
        $manager = $this->connection->createSchemaManager();
        $tables = $manager->listTableNames();
        $service = new CalendrierUniversitaireService();
        $calendriers = in_array('structure_calendrier', $tables, true)
            ? $this->connection->fetchAllAssociative('SELECT * FROM structure_calendrier') : [];
        $groupes = $dates = [];
        foreach ($calendriers as $ligne) {
            $this->abortIf($ligne['annee_universitaire_id'] === null, 'Une semaine historique est sans année universitaire.');
            $groupes[$ligne['annee_universitaire_id']][] = $ligne;
            $dates[$ligne['id']] = $ligne['date_lundi'];
        }
        $columns = $manager->listTableColumns('structure_annee_universitaire');
        $hasDate = isset($columns['date_debut_semaine_formation']);
        $references = [];
        foreach ($groupes as $id => $lignes) {
            $row = $this->connection->fetchAssociative('SELECT * FROM structure_annee_universitaire WHERE id = ?', [$id]);
            $this->abortIf($row === false, 'Année universitaire historique introuvable.');
            $debut = $service->getDateDebutDepuisCorrespondances($lignes);
            $annee = (new StructureAnneeUniversitaire())->setAnnee((int) $row['annee'])->setDateDebutSemaineFormation($debut);
            foreach ($lignes as $ligne) {
                $this->abortIf($service->getSemaineFormation($annee, new \DateTimeImmutable($ligne['date_lundi'])) !== (int) $ligne['semaine_formation'], 'Calendrier historique hors des bornes au 31 août N+1.');
            }
            $this->abortIf($hasDate && $row['date_debut_semaine_formation'] !== null && $row['date_debut_semaine_formation'] !== $debut->format('Y-m-d'), 'Date annuelle déjà configurée différente du calendrier historique.');
            $references[$id] = $debut->format('Y-m-d');
        }

        // Préparer toutes les transformations avant de programmer la première DDL MySQL.
        $contraintesReprises = [];
        if (in_array('edt_contraintes_semestre', $tables, true)) {
            $duplicates = $this->connection->fetchOne('SELECT COUNT(*) FROM (SELECT semestre_id, annee_universitaire_id FROM edt_contraintes_semestre GROUP BY semestre_id, annee_universitaire_id HAVING COUNT(*) > 1) doublons');
            $this->abortIf((int) $duplicates > 0, 'Plusieurs calendriers de contraintes existent pour un même semestre et une même année.');
            foreach ($this->connection->fetchAllAssociative('SELECT id, contraintes FROM edt_contraintes_semestre') as $row) {
                $valeurs = json_decode($row['contraintes'] ?? '{}', true, flags: JSON_THROW_ON_ERROR);
                $this->abortIf(!is_array($valeurs), 'Contraintes historiques invalides.');
                $nouvelles = [];
                foreach ($valeurs as $key => $value) {
                    $date = $dates[$key] ?? (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $key) ? (string) $key : null);
                    $this->abortIf($date === null || array_key_exists($date, $nouvelles), 'Contrainte sans correspondance calendrier unique.');
                    $nouvelles[$date] = $value;
                }
                $contraintesReprises[$row['id']] = json_encode((object) $nouvelles, JSON_THROW_ON_ERROR);
            }
        }
        $foreignKeys = [];
        foreach ($tables as $table) {
            foreach ($manager->listTableForeignKeys($table) as $fk) {
                if ($fk->getForeignTableName() !== 'structure_calendrier') continue;
                $this->abortIf(count($fk->getLocalColumns()) !== 1 || $fk->getForeignColumns() !== ['id'], 'Référence calendrier composite non prise en charge.');
                $this->abortIf(isset($manager->listTableColumns($table)['date_lundi']), 'La table '.$table.' possède déjà une date_lundi : vérifier sa migration.');
                $foreignKeys[] = [$table, $fk];
            }
        }

        if (!$hasDate) $this->addSql('ALTER TABLE structure_annee_universitaire ADD date_debut_semaine_formation DATE DEFAULT NULL');
        foreach ($references as $id => $date) {
            $this->addSql('UPDATE structure_annee_universitaire SET date_debut_semaine_formation = ? WHERE id = ?', [$date, $id]);
        }
        if (!in_array('edt_contraintes_semestre', $tables, true)) {
            $this->addSql('CREATE TABLE edt_contraintes_semestre (id INT AUTO_INCREMENT NOT NULL, semestre_id INT NOT NULL, annee_universitaire_id INT NOT NULL, contraintes JSON NOT NULL, UNIQUE INDEX UNIQ_EDT_CONTRAINTES_ANNEE_SEMESTRE (annee_universitaire_id, semestre_id), INDEX IDX_EDT_CONTRAINTES_SEMESTRE (semestre_id), PRIMARY KEY(id), CONSTRAINT FK_EDT_CONTRAINTES_ANNEE FOREIGN KEY (annee_universitaire_id) REFERENCES structure_annee_universitaire (id) ON DELETE CASCADE, CONSTRAINT FK_EDT_CONTRAINTES_SEMESTRE FOREIGN KEY (semestre_id) REFERENCES structure_semestre (id) ON DELETE CASCADE) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        }
        if (in_array('edt_contraintes_semestre', $tables, true)) {
            $hasUnique = false;
            foreach ($manager->listTableIndexes('edt_contraintes_semestre') as $index) {
                if ($index->isUnique() && $index->getColumns() === ['annee_universitaire_id', 'semestre_id']) $hasUnique = true;
            }
            if (!$hasUnique) $this->addSql('CREATE UNIQUE INDEX UNIQ_EDT_CONTRAINTES_ANNEE_SEMESTRE ON edt_contraintes_semestre (annee_universitaire_id, semestre_id)');
        }
        foreach ($contraintesReprises as $id => $json) {
            $this->addSql('UPDATE edt_contraintes_semestre SET contraintes = ? WHERE id = ?', [$json, $id]);
        }
        // Préserver aussi les éventuelles tables historiques qui référencent une semaine par FK.
        foreach ($foreignKeys as [$table, $fk]) {
            $table = $this->connection->quoteIdentifier($table);
            $column = $this->connection->quoteIdentifier($fk->getLocalColumns()[0]);
            $name = $this->connection->quoteIdentifier($fk->getName());
            $this->addSql("ALTER TABLE $table ADD date_lundi DATE DEFAULT NULL");
            $this->addSql("UPDATE $table t INNER JOIN structure_calendrier c ON t.$column = c.id SET t.date_lundi = c.date_lundi");
            $this->addSql("ALTER TABLE $table DROP FOREIGN KEY $name");
            $this->addSql("ALTER TABLE $table DROP COLUMN $column");
        }
        if (in_array('structure_calendrier', $tables, true)) $this->addSql('DROP TABLE structure_calendrier');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Les identifiants des anciennes semaines ne sont pas reconstructibles ; restaurer une sauvegarde.');
    }
}
