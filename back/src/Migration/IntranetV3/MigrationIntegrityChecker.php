<?php

declare(strict_types=1);

namespace App\Migration\IntranetV3;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Contrôles d'intégrité métier après transfert (source V3 distincte de V4).
 * Les avertissements de cardinalité ne valent pas suppression automatique.
 */
final readonly class MigrationIntegrityChecker
{
    public function __construct(
        #[Autowire(service: 'doctrine.dbal.copy_connection')]
        private Connection $source,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return list<array{control:string,source:int|string,target:int|string,status:string,details:string}>
     */
    public function check(): array
    {
        $rows = [];
        $target = $this->entityManager->getConnection();

        $expectedScolarities = (int) $this->source->fetchOne(<<<'SQL'
SELECT COUNT(*) FROM (
    SELECT sc.etudiant_id, sc.annee_universitaire_id
    FROM scolarite sc
    WHERE sc.annee_universitaire_id IS NOT NULL
    GROUP BY sc.etudiant_id, sc.annee_universitaire_id
) annual_records
SQL);
        $actualScolarities = (int) $target->fetchOne('SELECT COUNT(*) FROM etudiant_scolarite');
        $rows[] = $this->row(
            'Scolarités annuelles',
            $expectedScolarities,
            $actualScolarities,
            $expectedScolarities === $actualScolarities ? 'OK' : 'WARNING',
            'Comparer les couples étudiant–année ; les inscriptions invalides sont exclues du total V3.'
        );

        $duplicateScolarities = (int) $target->fetchOne(<<<'SQL'
SELECT COUNT(*) FROM (
    SELECT etudiant_id, annee_universitaire_id
    FROM etudiant_scolarite
    GROUP BY etudiant_id, annee_universitaire_id
    HAVING COUNT(*) > 1
) duplicate_keys
SQL);
        $rows[] = $this->row(
            'Couples étudiant–année en doublon',
            0,
            $duplicateScolarities,
            $duplicateScolarities === 0 ? 'OK' : 'ERROR',
            'Unicité attendue dans EtudiantScolarite (etudiant_id, annee_universitaire_id).'
        );

        $notesEligible = (int) $this->source->fetchOne(<<<'SQL'
SELECT COUNT(*)
FROM note n
INNER JOIN evaluation e ON e.id = n.evaluation_id
INNER JOIN etudiant etu ON etu.id = n.etudiant_id
WHERE e.type_matiere IN ('matiere', 'ressource', 'sae')
  AND etu.annee_sortie = 0
SQL);
        $notesTarget = (int) $target->fetchOne('SELECT COUNT(*) FROM etudiant_note');
        $rows[] = $this->row(
            'Notes détaillées des étudiants en cours',
            $notesEligible,
            $notesTarget,
            $notesEligible > 0 && $notesTarget === 0 ? 'ERROR' : ($notesTarget === $notesEligible ? 'OK' : 'WARNING'),
            'Les notes des étudiants sortis ne sont pas éligibles. Rapprocher les notes restantes par évaluation et inscription.'
        );

        $previsionnelsV3 = (int) $this->source->fetchOne(<<<'SQL'
SELECT COUNT(*) FROM previsionnel p
INNER JOIN annee_universitaire au ON au.annee = p.annee
WHERE au.annee = 2026
SQL);
        $previsionnelsV4 = (int) $target->fetchOne(
            'SELECT COUNT(*) FROM previsionnel p INNER JOIN structure_annee_universitaire au ON au.id = p.annee_universitaire_id WHERE au.annee = 2026'
        );
        $rows[] = $this->row(
            'Prévisionnels 2026–2027 (brut)',
            $previsionnelsV3,
            $previsionnelsV4,
            $previsionnelsV3 > 0 && $previsionnelsV4 === 0 ? 'ERROR' : ($previsionnelsV3 === $previsionnelsV4 ? 'OK' : 'WARNING'),
            'Les clés répétées et enseignements ambigus doivent être détaillés dans le rapport du migrateur.'
        );

        $orphanGroups = (int) $this->entityManager->createQuery(<<<'DQL'
SELECT COUNT(g.id)
FROM App\Entity\Structure\StructureGroupe g
WHERE g.semestres IS EMPTY
DQL)->getSingleScalarResult();
        $rows[] = $this->row(
            'Groupes V4 sans semestre',
            0,
            $orphanGroups,
            $orphanGroups === 0 ? 'OK' : 'WARNING',
            'Contrôler les groupes sans affectation et les restrictions de parcours.'
        );

        $activeCalendars = (int) $target->fetchOne(<<<'SQL'
SELECT COUNT(*)
FROM structure_annee_universitaire
WHERE annee = 2026 AND date_debut_semaine_formation IS NOT NULL
SQL);
        $rows[] = $this->row(
            'Calendrier 2026–2027 initialisé',
            1,
            $activeCalendars,
            $activeCalendars === 1 ? 'OK' : 'ERROR',
            'Le lundi de la semaine 1 doit être retrouvé depuis V3 (hors semaine 0).'
        );

        return $rows;
    }

    /** @return array{control:string,source:int|string,target:int|string,status:string,details:string} */
    private function row(string $control, int|string $source, int|string $target, string $status, string $details): array
    {
        return compact('control', 'source', 'target', 'status', 'details');
    }
}
