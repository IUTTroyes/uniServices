<?php

namespace App\Migration\IntranetV3\Scolarite;

use App\Entity\Edt\EdtEvent;
use App\Entity\Etudiant\EtudiantScolariteSemestre;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\Contract\MigratorInterface;
use App\Migration\IntranetV3\Edt\EdtEventMigrator;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;
use IntranetBundle\Entity\Etudiant\EtudiantAbsence;
use Symfony\Component\Uid\Uuid;

final class AbsenceActiveMigrator extends AbstractMigrator
{
    private const MAX_DIAGNOSTIC_SAMPLES = 20;

    public function getName(): string
    {
        return 'absences-actives';
    }

    /** @return list<class-string<MigratorInterface>> */
    public function getDependencies(): array
    {
        return [EdtEventMigrator::class, ScolariteMigrator::class];
    }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $created = $updated = $skipped = $failed = $processed = 0;
        $messages = [];
        $sampleCount = 0;
        $diagnostics = [
            'etudiant' => 0,
            'scolariteSemestre' => 0,
            'event' => 0,
        ];

        $sql = <<<'SQL'
SELECT
    a.id,
    HEX(a.uuid) AS uuid_hex,
    a.etudiant_id,
    a.personnel_id,
    a.`dateHeure` AS date_heure,
    a.date_justifie,
    a.annee_universitaire_id,
    a.semestre_id,
    a.id_edu_sign,
    a.type_matiere,
    a.id_matiere,
    (
        SELECT ep.id
        FROM edt_planning ep
        WHERE ep.annee_universitaire_id = a.annee_universitaire_id
          AND ep.semestre_id = a.semestre_id
          AND ep.type_matiere = a.type_matiere
          AND ep.id_matiere = a.id_matiere
          AND DATE(ep.date) = DATE(a.`dateHeure`)
          AND (
              (ep.heure_debut IS NOT NULL AND TIME(ep.heure_debut) = TIME(a.`dateHeure`))
              OR ep.heure_debut IS NULL
          )
        ORDER BY (ep.heure_debut IS NULL) ASC, ep.id ASC
        LIMIT 1
    ) AS edt_planning_id
FROM absence a
INNER JOIN annee_universitaire au ON au.id = a.annee_universitaire_id
WHERE au.active = 1
ORDER BY a.id
SQL;

        foreach ($this->source->executeQuery($sql)->iterateAssociative() as $row) {
            try {
                $etudiant = $this->entityManager->getRepository(Etudiant::class)
                    ->findOneBy(['oldId' => (int) $row['etudiant_id']]);
                if (null === $etudiant) {
                    ++$skipped;
                    ++$diagnostics['etudiant'];
                    $this->addSample($messages, $sampleCount, sprintf('Absence V3 #%s ignorée: étudiant non résolu.', $row['id']));
                    ++$processed;
                    $this->flushBatch($context, $processed);
                    continue;
                }

                $scolariteSemestre = $this->findScolariteSemestre(
                    $etudiant,
                    (int) $row['annee_universitaire_id'],
                    (int) $row['semestre_id'],
                );
                if (null === $scolariteSemestre) {
                    ++$skipped;
                    ++$diagnostics['scolariteSemestre'];
                    $this->addSample($messages, $sampleCount, sprintf('Absence V3 #%s ignorée: scolarité semestrielle active non résolue.', $row['id']));
                    ++$processed;
                    $this->flushBatch($context, $processed);
                    continue;
                }

                $event = null !== $row['edt_planning_id']
                    ? $this->entityManager->getRepository(EdtEvent::class)->findOneBy(['oldId' => (int) $row['edt_planning_id']])
                    : null;
                if (null === $event) {
                    ++$skipped;
                    ++$diagnostics['event'];
                    $this->addSample($messages, $sampleCount, sprintf('Absence V3 #%s ignorée: événement EDT correspondant non résolu.', $row['id']));
                    ++$processed;
                    $this->flushBatch($context, $processed);
                    continue;
                }

                $uuid = $this->uuidFromHex($row['uuid_hex']);
                $absence = $this->entityManager->getRepository(EtudiantAbsence::class)->findOneBy(['uuid' => $uuid]);
                $isNew = null === $absence;
                $absence ??= new EtudiantAbsence();
                $absence->setUuid($uuid);

                $personnel = null !== $row['personnel_id']
                    ? $this->entityManager->getRepository(Personnel::class)->findOneBy(['oldId' => (int) $row['personnel_id']])
                    : null;

                $absence
                    ->setPersonnel($personnel)
                    ->setScolariteSemestre($scolariteSemestre)
                    ->setEvent($event)
                    ->setDateJustification(!empty($row['date_justifie']) ? new \DateTime((string) $row['date_justifie']) : null);

                if (method_exists($absence, 'setIdEduSign')) {
                    $absence->setIdEduSign($row['id_edu_sign'] ?: null);
                }

                if ($isNew) {
                    $this->entityManager->persist($absence);
                    ++$created;
                } else {
                    ++$updated;
                }
            } catch (\Throwable $e) {
                ++$failed;
                $this->addSample($messages, $sampleCount, sprintf('Absence V3 #%s: %s', $row['id'], $e->getMessage()));
            }

            ++$processed;
            $this->flushBatch($context, $processed);
        }

        $this->flushAndClear($context);

        if (array_sum($diagnostics) > 0) {
            $messages[] = sprintf(
                'Résumé absences actives non migrées: étudiants=%d, scolarités semestrielles=%d, événements EDT=%d.',
                $diagnostics['etudiant'],
                $diagnostics['scolariteSemestre'],
                $diagnostics['event'],
            );
        }

        return new MigrationResult($created, $updated, $skipped, $failed, $messages);
    }

    private function findScolariteSemestre(Etudiant $etudiant, int $anneeOldId, int $semestreOldId): ?EtudiantScolariteSemestre
    {
        return $this->entityManager->createQueryBuilder()
            ->select('ss')
            ->from(EtudiantScolariteSemestre::class, 'ss')
            ->innerJoin('ss.scolarite', 'sc')
            ->innerJoin('sc.anneeUniversitaire', 'au')
            ->innerJoin('ss.semestre', 'sem')
            ->andWhere('sc.etudiant = :etudiant')
            ->andWhere('au.oldId = :anneeOldId')
            ->andWhere('sem.oldId = :semestreOldId')
            ->setParameter('etudiant', $etudiant)
            ->setParameter('anneeOldId', $anneeOldId)
            ->setParameter('semestreOldId', $semestreOldId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function uuidFromHex(?string $hex): Uuid
    {
        if (null === $hex || 32 !== strlen($hex)) {
            return Uuid::v4();
        }
        $hex = strtolower($hex);
        return Uuid::fromString(sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        ));
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
