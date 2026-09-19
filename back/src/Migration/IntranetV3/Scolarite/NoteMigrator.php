<?php

namespace App\Migration\IntranetV3\Scolarite;

use App\Entity\Etudiant\EtudiantNote;
use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Etudiant\EtudiantScolariteSemestre;
use App\Entity\Scolarite\ScolEvaluation;
use App\Entity\Users\Etudiant;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\Contract\MigratorInterface;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;
use Symfony\Component\Uid\Uuid;

final class NoteMigrator extends AbstractMigrator
{
    private const MAX_DIAGNOSTIC_SAMPLES = 20;

    public function getName(): string
    {
        return 'notes';
    }

    /** @return list<class-string<MigratorInterface>> */
    public function getDependencies(): array
    {
        return [EvaluationMigrator::class, ScolariteMigrator::class];
    }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $created = $updated = $skipped = $failed = $processed = 0;
        $messages = [];
        $diagnostics = [
            'evaluation' => 0,
            'etudiant' => 0,
            'scolarite' => 0,
        ];
        $partial = [
            'scolariteSemestre' => 0,
        ];
        $sampleCount = 0;

        $sql = <<<'SQL'
SELECT
    n.id,
    n.etudiant_id,
    n.evaluation_id,
    n.note,
    n.commentaire,
    n.absence_justifie,
    HEX(e.uuid) AS evaluation_uuid_hex
FROM note n
INNER JOIN evaluation e ON e.id = n.evaluation_id
WHERE e.type_matiere IN ('matiere', 'ressource', 'sae')
ORDER BY n.id
SQL;

        foreach ($this->source->executeQuery($sql)->iterateAssociative() as $row) {
            try {
                $evaluation = $this->entityManager->getRepository(ScolEvaluation::class)
                    ->findOneBy(['uuid' => $this->uuidFromHex($row['evaluation_uuid_hex'])]);

                if (null === $evaluation) {
                    ++$skipped;
                    ++$diagnostics['evaluation'];
                    $this->addSample($messages, $sampleCount, sprintf(
                        'Note V3 #%s ignorée: évaluation V3 #%s non résolue.',
                        $row['id'],
                        $row['evaluation_id'],
                    ));
                    ++$processed;
                    $this->flushBatch($context, $processed);
                    continue;
                }

                $etudiant = $this->entityManager->getRepository(Etudiant::class)
                    ->findOneBy(['oldId' => (int) $row['etudiant_id']]);

                if (null === $etudiant) {
                    ++$skipped;
                    ++$diagnostics['etudiant'];
                    $this->addSample($messages, $sampleCount, sprintf(
                        'Note V3 #%s ignorée: étudiant V3 #%s non résolu.',
                        $row['id'],
                        $row['etudiant_id'],
                    ));
                    ++$processed;
                    $this->flushBatch($context, $processed);
                    continue;
                }

                $anneeUniversitaire = $evaluation->getAnneeUniversitaire();
                $semestre = $evaluation->getSemestre();

                $scolarite = null !== $anneeUniversitaire
                    ? $this->entityManager->getRepository(EtudiantScolarite::class)->findOneBy([
                        'etudiant' => $etudiant,
                        'anneeUniversitaire' => $anneeUniversitaire,
                    ])
                    : null;

                if (null === $scolarite) {
                    ++$skipped;
                    ++$diagnostics['scolarite'];
                    $this->addSample($messages, $sampleCount, sprintf(
                        'Note V3 #%s ignorée: scolarité annuelle de l\'étudiant non résolue.',
                        $row['id'],
                    ));
                    ++$processed;
                    $this->flushBatch($context, $processed);
                    continue;
                }

                $scolariteSemestre = null !== $semestre
                    ? $this->entityManager->getRepository(EtudiantScolariteSemestre::class)->findOneBy([
                        'scolarite' => $scolarite,
                        'semestre' => $semestre,
                    ])
                    : null;

                if (null === $scolariteSemestre) {
                    ++$partial['scolariteSemestre'];
                }

                $note = $this->entityManager->getRepository(EtudiantNote::class)->findOneBy([
                    'evaluation' => $evaluation,
                    'scolarite' => $scolarite,
                ]);
                $isNew = null === $note;
                $note ??= new EtudiantNote();

                $note
                    ->setEvaluation($evaluation)
                    ->setScolarite($scolarite)
                    ->setScolariteSemestre($scolariteSemestre)
                    ->setCommentaire($row['commentaire'] ?: null)
                    ->setPubliee($evaluation->isVisible());

                $noteValue = null !== $row['note'] ? (float) $row['note'] : null;

                if ((bool) $row['absence_justifie']) {
                    $note->setPresenceStatut(EtudiantNote::STATUT_ABSENT_JUSTIFIE);
                    if (null !== $noteValue && $noteValue >= -0.01 && $noteValue <= 20) {
                        $note->setNote($noteValue);
                    } else {
                        $note->setNote(-0.01);
                    }
                } elseif (null !== $noteValue && $noteValue < 0) {
                    $note
                        ->setPresenceStatut(EtudiantNote::STATUT_ABSENT_INJUSTIFIE)
                        ->setNote(0.0);
                } else {
                    $note->setPresenceStatut(EtudiantNote::STATUT_PRESENT);
                    if (null !== $noteValue && $noteValue >= 0 && $noteValue <= 20) {
                        $note->setNote($noteValue);
                    }
                }

                if ($isNew) {
                    $this->entityManager->persist($note);
                    ++$created;
                } else {
                    ++$updated;
                }
            } catch (\Throwable $e) {
                ++$failed;
                $this->addSample($messages, $sampleCount, sprintf(
                    'Note V3 #%s: %s',
                    $row['id'],
                    $e->getMessage(),
                ));
            }

            ++$processed;
            $this->flushBatch($context, $processed);
        }

        $this->flushAndClear($context);

        if (array_sum($diagnostics) > 0) {
            $messages[] = sprintf(
                'Résumé notes non migrées: évaluations=%d, étudiants=%d, scolarités annuelles=%d.',
                $diagnostics['evaluation'],
                $diagnostics['etudiant'],
                $diagnostics['scolarite'],
            );
        }

        if ($partial['scolariteSemestre'] > 0) {
            $messages[] = sprintf(
                'Notes migrées sans rattachement semestriel précis: %d (scolarité annuelle conservée).',
                $partial['scolariteSemestre'],
            );
        }

        return new MigrationResult($created, $updated, $skipped, $failed, $messages);
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
