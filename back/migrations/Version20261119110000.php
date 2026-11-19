<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261119110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Supprime les compteurs CM, TD et TP stockés sur les semestres.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE structure_semestre DROP nb_groupes_cm, DROP nb_groupes_td, DROP nb_groupes_tp');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE structure_semestre ADD nb_groupes_cm INT NOT NULL, ADD nb_groupes_td INT NOT NULL, ADD nb_groupes_tp INT NOT NULL');
        $this->addSql(<<<'SQL'
            UPDATE structure_semestre s
            LEFT JOIN (
                SELECT lien.structure_semestre_id,
                       SUM(g.type = 'CM') AS cm,
                       SUM(g.type = 'TD') AS td,
                       SUM(g.type = 'TP') AS tp
                FROM structure_groupe_structure_semestre lien
                INNER JOIN structure_groupe g ON g.id = lien.structure_groupe_id
                GROUP BY lien.structure_semestre_id
            ) compte ON compte.structure_semestre_id = s.id
            SET s.nb_groupes_cm = COALESCE(NULLIF(compte.cm, 0), 1),
                s.nb_groupes_td = COALESCE(NULLIF(compte.td, 0), 1),
                s.nb_groupes_tp = COALESCE(NULLIF(compte.tp, 0), 2)
            SQL);
    }
}
