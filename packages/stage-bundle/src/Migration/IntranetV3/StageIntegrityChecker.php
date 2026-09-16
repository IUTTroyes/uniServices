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
        $checks = [
            ['Périodes de stage', 'SELECT COUNT(*) FROM stage_periode', 'SELECT COUNT(*) FROM stage_periode WHERE old_id IS NOT NULL'],
            ['Stages étudiants', 'SELECT COUNT(*) FROM stage_etudiant', 'SELECT COUNT(*) FROM stage_etudiant'],
            ['Stages sans période', 'SELECT COUNT(*) FROM stage_etudiant WHERE stage_periode_id IS NULL', 'SELECT COUNT(*) FROM stage_etudiant WHERE stage_periode_id IS NULL'],
            ['Stages avec entreprise', 'SELECT COUNT(*) FROM stage_etudiant WHERE entreprise_id IS NOT NULL', 'SELECT COUNT(*) FROM stage_etudiant WHERE entreprise_id IS NOT NULL'],
            ['Stages avec tuteur entreprise', 'SELECT COUNT(*) FROM stage_etudiant WHERE tuteur_id IS NOT NULL', 'SELECT COUNT(*) FROM stage_etudiant WHERE tuteur_id IS NOT NULL'],
            ['Stages avec tuteur universitaire', 'SELECT COUNT(*) FROM stage_etudiant WHERE tuteur_universitaire_id IS NOT NULL', 'SELECT COUNT(*) FROM stage_etudiant WHERE tuteur_universitaire_id IS NOT NULL'],
            ['Entreprises référencées', 'SELECT COUNT(DISTINCT entreprise_id) FROM stage_etudiant WHERE entreprise_id IS NOT NULL', 'SELECT COUNT(DISTINCT entreprise_id) FROM stage_etudiant WHERE entreprise_id IS NOT NULL'],
            ['Tuteurs entreprise référencés', 'SELECT COUNT(DISTINCT tuteur_id) FROM stage_etudiant WHERE tuteur_id IS NOT NULL', 'SELECT COUNT(DISTINCT tuteur_id) FROM stage_etudiant WHERE tuteur_id IS NOT NULL'],
            ['Responsables de période', 'SELECT COUNT(*) FROM stage_periode_personnel', 'SELECT (SELECT COUNT(*) FROM stage_periode WHERE responsable_principal_id IS NOT NULL) + (SELECT COUNT(*) FROM stage_periode_personnel)'],
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
