<?php

namespace App\Migration\IntranetV3\Scolarite;

use App\Entity\Personnel\PersonnelEnseignantHrs;
use App\Entity\Personnel\PersonnelEnseignantTypeHrs;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Users\Personnel;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\Contract\MigratorInterface;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;
use App\Migration\IntranetV3\Structure\AnneeUniversitaireMigrator;
use App\Migration\IntranetV3\Structure\SemestreMigrator;
use App\Migration\IntranetV3\Users\PersonnelMigrator;

final class HrsMigrator extends AbstractMigrator
{
    private const MAX_DIAGNOSTIC_SAMPLES = 20;

    public function getName(): string
    {
        return 'hrs-actifs';
    }

    /** @return list<class-string<MigratorInterface>> */
    public function getDependencies(): array
    {
        return [
            TypeHrsMigrator::class,
            PersonnelMigrator::class,
            AnneeUniversitaireMigrator::class,
            SemestreMigrator::class,
        ];
    }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $created = $updated = $skipped = $failed = $processed = 0;
        $messages = [];
        $sampleCount = 0;
        $diagnostics = [
            'personnel' => 0,
            'annee' => 0,
            'type' => 0,
            'semestre' => 0,
            'diplome' => 0,
        ];

        $sql = <<<'SQL'
SELECT
    h.id,
    h.nb_heures_td,
    h.libelle,
    h.commentaire,
    h.semestre_id,
    h.diplome_id,
    h.personnel_id,
    h.type_hrs_id,
    h.annee,
    th.libelle AS type_hrs_libelle,
    th.type AS type_hrs_type
FROM hrs h
INNER JOIN annee_universitaire au ON au.annee = h.annee
LEFT JOIN type_hrs th ON th.id = h.type_hrs_id
WHERE au.active = 1
ORDER BY h.id
SQL;

        foreach ($this->source->executeQuery($sql)->iterateAssociative() as $row) {
            try {
                $personnel = $this->entityManager->getRepository(Personnel::class)
                    ->findOneBy(['oldId' => (int) $row['personnel_id']]);
                $anneeUniversitaire = $this->entityManager->getRepository(StructureAnneeUniversitaire::class)
                    ->findOneBy(['annee' => (int) $row['annee'], 'actif' => true]);
                $typeHrs = $this->findTypeHrs($row);

                if (null === $personnel) {
                    ++$skipped;
                    ++$diagnostics['personnel'];
                    $this->sample($messages, $sampleCount, sprintf('HRS V3 #%s ignorée: personnel #%s non résolu.', $row['id'], $row['personnel_id']));
                    ++$processed;
                    $this->flushBatch($context, $processed);
                    continue;
                }
                if (null === $anneeUniversitaire) {
                    ++$skipped;
                    ++$diagnostics['annee'];
                    ++$processed;
                    $this->flushBatch($context, $processed);
                    continue;
                }
                if (null === $typeHrs) {
                    ++$skipped;
                    ++$diagnostics['type'];
                    ++$processed;
                    $this->flushBatch($context, $processed);
                    continue;
                }

                $diplome = null;
                if (null !== $row['diplome_id']) {
                    $diplome = $this->entityManager->getRepository(StructureDiplome::class)
                        ->findOneBy(['oldId' => (int) $row['diplome_id']]);
                    if (null === $diplome) {
                        ++$diagnostics['diplome'];
                    }
                }

                $semestre = null;
                if (null !== $row['semestre_id']) {
                    $semestre = $this->findSnapshotSemestre((int) $row['semestre_id'], $anneeUniversitaire);
                    if (null === $semestre) {
                        ++$diagnostics['semestre'];
                    }
                }

                $entity = $this->entityManager->getRepository(PersonnelEnseignantHrs::class)->findOneBy([
                    'personnel' => $personnel,
                    'annee_universitaire' => $anneeUniversitaire,
                    'enseignantTypeHrs' => $typeHrs,
                    'libelle' => (string) $row['libelle'],
                    'semestre' => $semestre,
                    'diplome' => $diplome,
                ]);
                $isNew = null === $entity;
                $entity ??= new PersonnelEnseignantHrs();

                $entity
                    ->setPersonnel($personnel)
                    ->setAnneeUniversitaire($anneeUniversitaire)
                    ->setEnseignantTypeHrs($typeHrs)
                    ->setLibelle((string) $row['libelle'])
                    ->setCommentaire($row['commentaire'] ?: null)
                    ->setNbHeuresTd((float) ($row['nb_heures_td'] ?? 0))
                    ->setSemestre($semestre)
                    ->setDiplome($diplome);

                if ($isNew) {
                    $this->entityManager->persist($entity);
                    ++$created;
                } else {
                    ++$updated;
                }
            } catch (\Throwable $e) {
                ++$failed;
                $this->sample($messages, $sampleCount, sprintf('HRS V3 #%s: %s', $row['id'], $e->getMessage()));
            }

            ++$processed;
            $this->flushBatch($context, $processed);
        }

        $this->flushAndClear($context);

        if (array_sum($diagnostics) > 0) {
            $messages[] = sprintf(
                'Résumé HRS actives: personnels=%d, années=%d, types=%d, semestres non résolus=%d, diplômes non résolus=%d.',
                $diagnostics['personnel'],
                $diagnostics['annee'],
                $diagnostics['type'],
                $diagnostics['semestre'],
                $diagnostics['diplome'],
            );
        }

        return new MigrationResult($created, $updated, $skipped, $failed, $messages);
    }

    /** @param array<string,mixed> $row */
    private function findTypeHrs(array $row): ?PersonnelEnseignantTypeHrs
    {
        if (empty($row['type_hrs_libelle'])) {
            return null;
        }

        return $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(PersonnelEnseignantTypeHrs::class, 't')
            ->andWhere('t.libelle = :libelle')
            ->andWhere('(t.type = :type OR (:type IS NULL AND t.type IS NULL))')
            ->setParameter('libelle', (string) $row['type_hrs_libelle'])
            ->setParameter('type', $row['type_hrs_type'] ?: null)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function findSnapshotSemestre(int $oldId, StructureAnneeUniversitaire $anneeUniversitaire): ?StructureSemestre
    {
        return $this->entityManager->createQueryBuilder()
            ->select('s')
            ->from(StructureSemestre::class, 's')
            ->innerJoin('s.annee', 'a')
            ->innerJoin('a.pn', 'pn')
            ->andWhere('s.oldId = :oldId')
            ->andWhere('pn.anneeUniversitaire = :anneeUniversitaire')
            ->setParameter('oldId', $oldId)
            ->setParameter('anneeUniversitaire', $anneeUniversitaire)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @param list<string> $messages */
    private function sample(array &$messages, int &$sampleCount, string $message): void
    {
        if ($sampleCount >= self::MAX_DIAGNOSTIC_SAMPLES) {
            return;
        }

        $messages[] = $message;
        ++$sampleCount;
    }
}
