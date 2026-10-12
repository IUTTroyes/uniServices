<?php

namespace StageBundle\Migration\IntranetV3;

use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class StageIntegrityChecker
{
    public function __construct(
        #[Autowire(service: 'doctrine.dbal.copy_connection')]
        private Connection $source,
        private Connection $target,
    ) {}

    /** @return list<array{label:string, source:int, target:int, ok:bool}> */
    public function check(): array
    {
        $activeStages = <<<'SQL'
SELECT se.id
FROM stage_etudiant se
INNER JOIN stage_periode sp ON sp.id = se.stage_periode_id
INNER JOIN annee_universitaire au ON au.id = sp.annee_universitaire_id
WHERE au.active = 1
SQL;
        $activePeriods = <<<'SQL'
SELECT sp.id
FROM stage_periode sp
INNER JOIN annee_universitaire au ON au.id = sp.annee_universitaire_id
WHERE au.active = 1
SQL;

        $checks = [
            ['Périodes de stage (année active)', 'SELECT COUNT(*) FROM ('.$activePeriods.') p', 'SELECT COUNT(*) FROM stage_periode WHERE old_id IS NOT NULL'],
            ['Stages étudiants (périodes actives)', 'SELECT COUNT(*) FROM ('.$activeStages.') s', 'SELECT COUNT(*) FROM stage_etudiant'],
            ['Stages avec entreprise', 'SELECT COUNT(*) FROM stage_etudiant se WHERE se.id IN ('.$activeStages.') AND se.entreprise_id IS NOT NULL', 'SELECT COUNT(*) FROM stage_etudiant WHERE entreprise_id IS NOT NULL'],
            ['Stages avec tuteur entreprise', 'SELECT COUNT(*) FROM stage_etudiant se WHERE se.id IN ('.$activeStages.') AND se.tuteur_id IS NOT NULL', 'SELECT COUNT(*) FROM stage_etudiant WHERE tuteur_id IS NOT NULL'],
            ['Stages avec tuteur universitaire', 'SELECT COUNT(*) FROM stage_etudiant se WHERE se.id IN ('.$activeStages.') AND se.tuteur_universitaire_id IS NOT NULL', 'SELECT COUNT(*) FROM stage_etudiant WHERE tuteur_universitaire_id IS NOT NULL'],
            ['Entreprises référencées', 'SELECT COUNT(DISTINCT se.entreprise_id) FROM stage_etudiant se WHERE se.id IN ('.$activeStages.') AND se.entreprise_id IS NOT NULL', 'SELECT COUNT(DISTINCT entreprise_id) FROM stage_etudiant WHERE entreprise_id IS NOT NULL'],
            ['Tuteurs entreprise référencés', 'SELECT COUNT(DISTINCT se.tuteur_id) FROM stage_etudiant se WHERE se.id IN ('.$activeStages.') AND se.tuteur_id IS NOT NULL', 'SELECT COUNT(DISTINCT tuteur_id) FROM stage_etudiant WHERE tuteur_id IS NOT NULL'],
            ['Responsables de période', 'SELECT COUNT(*) FROM stage_periode_personnel spp WHERE spp.stage_periode_id IN ('.$activePeriods.')', 'SELECT (SELECT COUNT(*) FROM stage_periode WHERE old_id IS NOT NULL AND responsable_principal_id IS NOT NULL) + (SELECT COUNT(*) FROM stage_periode_personnel spp INNER JOIN stage_periode sp ON sp.id = spp.stage_periode_id WHERE sp.old_id IS NOT NULL)'],
        ];

        $rows = [];
        foreach ($checks as [$label, $sourceSql, $targetSql]) {
            $source = (int) $this->source->fetchOne($sourceSql);
            $target = (int) $this->target->fetchOne($targetSql);
            $rows[] = ['label' => $label, 'source' => $source, 'target' => $target, 'ok' => $source === $target];
        }

        return $rows;
    }
}
