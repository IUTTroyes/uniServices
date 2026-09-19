<?php

namespace App\Migration\IntranetV3\Scolarite;

use App\Entity\Etudiant\EtudiantScolariteSemestre;
use App\Entity\Structure\StructureGroupe;
use App\Entity\Users\Etudiant;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\Contract\MigratorInterface;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;
use App\Migration\IntranetV3\Structure\GroupeMigrator;
use App\Migration\IntranetV3\Users\EtudiantMigrator;

final class EtudiantGroupeMigrator extends AbstractMigrator
{
    private const MAX_DIAGNOSTIC_SAMPLES = 20;

    public function getName(): string
    {
        return 'etudiant-groupes';
    }

    /** @return list<class-string<MigratorInterface>> */
    public function getDependencies(): array
    {
        return [EtudiantMigrator::class, ScolariteMigrator::class, GroupeMigrator::class];
    }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $created = $updated = $skipped = $failed = $processed = 0;
        $messages = [];
        $unresolved = [
            'etudiant' => 0,
            'scolariteSemestre' => 0,
            'groupe' => 0,
        ];
        $legacyDiagnostics = [
            'semestreCourantAbsent' => 0,
            'scolariteCouranteAbsente' => 0,
            'groupeIncompatibleSemestreCourant' => 0,
            'incoherentAutre' => 0,
        ];
        $sampleCount = 0;

        $schemaManager = $this->source->createSchemaManager();
        $hasTypeGroupeSemestre = $schemaManager->tablesExist(['type_groupe_semestre']);

        $sql = <<<'SQL'
SELECT eg.etudiant_id, eg.groupe_id, e.semestre_id AS semestre_courant_id
FROM etudiant_groupe eg
INNER JOIN etudiant e ON e.id = eg.etudiant_id
ORDER BY eg.etudiant_id, eg.groupe_id
SQL;

        foreach ($this->source->executeQuery($sql)->iterateAssociative() as $row) {
            try {
                $etudiant = $this->entityManager->getRepository(Etudiant::class)
                    ->findOneBy(['oldId' => (int) $row['etudiant_id']]);

                if (null === $etudiant) {
                    ++$skipped;
                    ++$unresolved['etudiant'];
                    $this->addSample($messages, $sampleCount, sprintf(
                        'Affectation étudiant V3 #%s / groupe V3 #%s ignorée: étudiant non résolu.',
                        $row['etudiant_id'],
                        $row['groupe_id'],
                    ));
                    ++$processed;
                    $this->flushBatch($context, $processed);
                    continue;
                }

                /*
                 * V3 ne versionne pas etudiant_groupe par année universitaire.
                 * On rattache donc l'affectation au semestre le plus récent dans lequel
                 * l'étudiant possède une scolarité et où ce groupe V3 existe dans le snapshot.
                 */
                $scolariteSemestre = $this->entityManager->createQueryBuilder()
                    ->select('ss')
                    ->from(EtudiantScolariteSemestre::class, 'ss')
                    ->innerJoin('ss.scolarite', 'sc')
                    ->innerJoin('ss.semestre', 'sem')
                    ->innerJoin('sem.annee', 'an')
                    ->innerJoin('an.pn', 'pn')
                    ->innerJoin('pn.anneeUniversitaire', 'au')
                    ->innerJoin('sem.groupes', 'g')
                    ->andWhere('sc.etudiant = :etudiant')
                    ->andWhere('g.oldId = :groupeOldId')
                    ->setParameter('etudiant', $etudiant)
                    ->setParameter('groupeOldId', (int) $row['groupe_id'])
                    ->orderBy('au.annee', 'DESC')
                    ->addOrderBy('sem.ordreLmd', 'DESC')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();

                if (null === $scolariteSemestre) {
                    ++$skipped;
                    ++$unresolved['scolariteSemestre'];

                    $reason = $this->classifyLegacyMismatch(
                        (int) $row['etudiant_id'],
                        (int) $row['groupe_id'],
                        null !== $row['semestre_courant_id'] ? (int) $row['semestre_courant_id'] : null,
                        $hasTypeGroupeSemestre,
                        $legacyDiagnostics,
                    );

                    $this->addSample($messages, $sampleCount, sprintf(
                        'Affectation étudiant V3 #%s / groupe V3 #%s ignorée: aucune scolarité semestrielle compatible trouvée (%s).',
                        $row['etudiant_id'],
                        $row['groupe_id'],
                        $reason,
                    ));
                    ++$processed;
                    $this->flushBatch($context, $processed);
                    continue;
                }

                $groupe = null;
                foreach ($scolariteSemestre->getSemestre()->getGroupes() as $candidate) {
                    if ($candidate->getOldId() === (int) $row['groupe_id']) {
                        $groupe = $candidate;
                        break;
                    }
                }

                if (!$groupe instanceof StructureGroupe) {
                    ++$skipped;
                    ++$unresolved['groupe'];
                    $this->addSample($messages, $sampleCount, sprintf(
                        'Affectation étudiant V3 #%s / groupe V3 #%s ignorée: groupe du snapshot résolu introuvable.',
                        $row['etudiant_id'],
                        $row['groupe_id'],
                    ));
                    ++$processed;
                    $this->flushBatch($context, $processed);
                    continue;
                }

                if (!$scolariteSemestre->getGroupes()->contains($groupe)) {
                    $scolariteSemestre->addGroupe($groupe);
                    ++$created;
                } else {
                    ++$updated;
                }
            } catch (\Throwable $e) {
                ++$failed;
                $this->addSample($messages, $sampleCount, sprintf(
                    'Affectation étudiant V3 #%s / groupe V3 #%s: %s',
                    $row['etudiant_id'],
                    $row['groupe_id'],
                    $e->getMessage(),
                ));
            }

            ++$processed;
            $this->flushBatch($context, $processed);
        }

        $this->flushAndClear($context);

        if (array_sum($unresolved) > 0) {
            $messages[] = sprintf(
                'Résumé des affectations non résolues: étudiants=%d, scolarités semestrielles compatibles=%d, groupes snapshots=%d.',
                $unresolved['etudiant'],
                $unresolved['scolariteSemestre'],
                $unresolved['groupe'],
            );
        }

        if (array_sum($legacyDiagnostics) > 0) {
            $messages[] = sprintf(
                'Diagnostic V3 des affectations non résolues: semestre courant absent=%d, aucune scolarité sur le semestre courant=%d, groupe incompatible avec le semestre courant=%d, autres incohérences=%d.',
                $legacyDiagnostics['semestreCourantAbsent'],
                $legacyDiagnostics['scolariteCouranteAbsente'],
                $legacyDiagnostics['groupeIncompatibleSemestreCourant'],
                $legacyDiagnostics['incoherentAutre'],
            );
        }

        return new MigrationResult($created, $updated, $skipped, $failed, $messages);
    }

    /** @param array<string, int> $diagnostics */
    private function classifyLegacyMismatch(
        int $etudiantOldId,
        int $groupeOldId,
        ?int $semestreCourantOldId,
        bool $hasTypeGroupeSemestre,
        array &$diagnostics,
    ): string {
        if (null === $semestreCourantOldId) {
            ++$diagnostics['semestreCourantAbsent'];

            return 'semestre courant absent dans V3';
        }

        $hasCurrentScolarite = (bool) $this->source->fetchOne(
            'SELECT COUNT(*) FROM scolarite WHERE etudiant_id = :etudiant AND semestre_id = :semestre AND annee_universitaire_id IS NOT NULL',
            ['etudiant' => $etudiantOldId, 'semestre' => $semestreCourantOldId],
        );

        if (!$hasCurrentScolarite) {
            ++$diagnostics['scolariteCouranteAbsente'];

            return sprintf('aucune scolarité V3 sur le semestre courant #%d', $semestreCourantOldId);
        }

        if ($hasTypeGroupeSemestre) {
            $groupMatchesCurrentSemester = (bool) $this->source->fetchOne(
                <<<'SQL'
SELECT COUNT(*)
FROM groupe g
INNER JOIN type_groupe tg ON tg.id = g.type_groupe_id
INNER JOIN type_groupe_semestre tgs ON tgs.type_groupe_id = tg.id
WHERE g.id = :groupe AND tgs.semestre_id = :semestre
SQL,
                ['groupe' => $groupeOldId, 'semestre' => $semestreCourantOldId],
            );
        } else {
            $groupMatchesCurrentSemester = (bool) $this->source->fetchOne(
                <<<'SQL'
SELECT COUNT(*)
FROM groupe g
INNER JOIN type_groupe tg ON tg.id = g.type_groupe_id
WHERE g.id = :groupe AND tg.semestre_id = :semestre
SQL,
                ['groupe' => $groupeOldId, 'semestre' => $semestreCourantOldId],
            );
        }

        if (!$groupMatchesCurrentSemester) {
            ++$diagnostics['groupeIncompatibleSemestreCourant'];

            return sprintf('groupe incompatible avec le semestre courant V3 #%d', $semestreCourantOldId);
        }

        ++$diagnostics['incoherentAutre'];

        return 'références V3 cohérentes mais aucun snapshot cible compatible';
    }

    /** @param list<string> $messages */
    private function addSample(array &$messages, int &$sampleCount, string $message): void
    {
        if ($sampleCount >= self::MAX_DIAGNOSTIC_SAMPLES) {
            return;
        }

        $messages[] = $message;
        ++$sampleCount;
    }
}
