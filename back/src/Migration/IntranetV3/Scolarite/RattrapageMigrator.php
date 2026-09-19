<?php

namespace App\Migration\IntranetV3\Scolarite;

use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Scolarite\ScolEvaluation;
use App\Entity\Scolarite\ScolEvaluationRattrapage;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Enum\TypeEnseignementEnum;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\Contract\MigratorInterface;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;
use App\Migration\IntranetV3\Users\EtudiantMigrator;
use App\Migration\IntranetV3\Users\PersonnelMigrator;
use Symfony\Component\Uid\Uuid;

final class RattrapageMigrator extends AbstractMigrator
{
    private const MAX_DIAGNOSTIC_SAMPLES = 20;

    public function getName(): string
    {
        return 'rattrapages';
    }

    /** @return list<class-string<MigratorInterface>> */
    public function getDependencies(): array
    {
        return [EvaluationMigrator::class, EtudiantMigrator::class, PersonnelMigrator::class];
    }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $created = $updated = $skipped = $failed = $processed = 0;
        $messages = [];
        $sampleCount = 0;
        $diagnostics = [
            'etudiant' => 0,
            'evaluation' => 0,
            'uuid' => 0,
        ];

        $activeYear = $this->entityManager->getRepository(StructureAnneeUniversitaire::class)
            ->findOneBy(['actif' => true]);

        if (null === $activeYear || null === $activeYear->getOldId()) {
            return new MigrationResult(0, 0, 0, 1, ['Rattrapages: année universitaire active non résolue.']);
        }

        $sql = <<<'SQL'
SELECT
    r.id,
    HEX(r.uuid) AS uuid_hex,
    r.etudiant_id,
    r.personnel_id,
    r.semestre_id,
    r.annee_universitaire_id,
    r.type_matiere,
    r.id_matiere,
    r.date_eval,
    r.heure_eval,
    r.duree,
    r.date_rattrapage,
    r.heure_rattrapage,
    r.salle,
    r.etat_demande
FROM rattrapage r
INNER JOIN annee_universitaire au ON au.id = r.annee_universitaire_id
WHERE au.active = 1
ORDER BY r.id
SQL;

        foreach ($this->source->executeQuery($sql)->iterateAssociative() as $row) {
            try {
                $uuid = $this->uuidFromHex($row['uuid_hex']);
                if (null === $uuid) {
                    ++$skipped;
                    ++$diagnostics['uuid'];
                    $this->addSample($messages, $sampleCount, sprintf('Rattrapage V3 #%s ignoré: UUID invalide.', $row['id']));
                    ++$processed;
                    $this->flushBatch($context, $processed);
                    continue;
                }

                $etudiant = $this->entityManager->getRepository(Etudiant::class)
                    ->findOneBy(['oldId' => (int) $row['etudiant_id']]);
                if (null === $etudiant) {
                    ++$skipped;
                    ++$diagnostics['etudiant'];
                    $this->addSample($messages, $sampleCount, sprintf('Rattrapage V3 #%s ignoré: étudiant V3 #%s non résolu.', $row['id'], $row['etudiant_id']));
                    ++$processed;
                    $this->flushBatch($context, $processed);
                    continue;
                }

                $type = $this->mapType($row['type_matiere'] ?? null);
                $evaluation = null !== $type
                    ? $this->findEvaluation(
                        $activeYear,
                        (int) $row['semestre_id'],
                        (int) $row['id_matiere'],
                        $type,
                        $row['date_eval'] ?: null,
                    )
                    : null;

                if (null === $evaluation) {
                    ++$skipped;
                    ++$diagnostics['evaluation'];
                    $this->addSample($messages, $sampleCount, sprintf(
                        'Rattrapage V3 #%s ignoré: évaluation non résolue (semestre #%s, %s #%s, date %s).',
                        $row['id'],
                        $row['semestre_id'],
                        $row['type_matiere'] ?? '?',
                        $row['id_matiere'],
                        $row['date_eval'] ?? 'NULL',
                    ));
                    ++$processed;
                    $this->flushBatch($context, $processed);
                    continue;
                }

                $entity = $this->entityManager->getRepository(ScolEvaluationRattrapage::class)
                    ->findOneBy(['uuid' => $uuid]);
                $isNew = null === $entity;
                $entity ??= new ScolEvaluationRattrapage();
                $entity->setUuid($uuid);

                $personnel = null !== $row['personnel_id']
                    ? $this->entityManager->getRepository(Personnel::class)->findOneBy(['oldId' => (int) $row['personnel_id']])
                    : null;

                $date = $this->date($row['date_rattrapage'] ?: null);
                $heureDebut = $this->time($row['heure_rattrapage'] ?: null);
                $heureFin = $this->computeEndTime($heureDebut, $row['duree'] ?: null);

                $entity
                    ->setEtudiant($etudiant)
                    ->setEvaluation($evaluation)
                    ->setPersonnel($personnel)
                    ->setEtat($this->mapEtat($row['etat_demande'] ?? null))
                    ->setDate($date)
                    ->setHeureDebut($heureDebut)
                    ->setHeureFin($heureFin);

                // Le champ V3 `salle` est une chaîne libre alors que la cible attend une entité Salle.
                // On ne fabrique pas de correspondance implicite ici.
                $entity->setSalle(null);

                if ($isNew) {
                    $this->entityManager->persist($entity);
                    ++$created;
                } else {
                    ++$updated;
                }
            } catch (\Throwable $e) {
                ++$failed;
                $this->addSample($messages, $sampleCount, sprintf('Rattrapage V3 #%s: %s', $row['id'], $e->getMessage()));
            }

            ++$processed;
            $this->flushBatch($context, $processed);
        }

        $this->flushAndClear($context);

        if (array_sum($diagnostics) > 0) {
            $messages[] = sprintf(
                'Résumé rattrapages non migrés (année active uniquement): étudiants=%d, évaluations=%d, UUID=%d.',
                $diagnostics['etudiant'],
                $diagnostics['evaluation'],
                $diagnostics['uuid'],
            );
        }

        return new MigrationResult($created, $updated, $skipped, $failed, $messages);
    }

    private function findEvaluation(
        StructureAnneeUniversitaire $anneeUniversitaire,
        int $semestreOldId,
        int $enseignementOldId,
        TypeEnseignementEnum $type,
        ?string $date,
    ): ?ScolEvaluation {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('e')
            ->from(ScolEvaluation::class, 'e')
            ->innerJoin('e.semestre', 's')
            ->innerJoin('e.enseignement', 'ens')
            ->andWhere('e.anneeUniversitaire = :anneeUniversitaire')
            ->andWhere('s.oldId = :semestreOldId')
            ->andWhere('ens.oldId = :enseignementOldId')
            ->andWhere('ens.type = :type')
            ->setParameter('anneeUniversitaire', $anneeUniversitaire)
            ->setParameter('semestreOldId', $semestreOldId)
            ->setParameter('enseignementOldId', $enseignementOldId)
            ->setParameter('type', $type)
            ->setMaxResults(1);

        if (null !== $date) {
            $qb->andWhere('e.date = :date')
                ->setParameter('date', new \DateTime($date));
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    private function mapType(?string $type): ?TypeEnseignementEnum
    {
        return match ($type) {
            'matiere' => TypeEnseignementEnum::TYPE_MATIERE,
            'ressource' => TypeEnseignementEnum::TYPE_RESSOURCE,
            'sae' => TypeEnseignementEnum::TYPE_SAE,
            default => null,
        };
    }

    private function mapEtat(?string $etat): ?bool
    {
        return match ($etat) {
            'a' => true,
            'r' => false,
            default => null,
        };
    }

    private function date(?string $value): ?\DateTime
    {
        return null !== $value && '' !== $value ? new \DateTime($value) : null;
    }

    private function time(?string $value): ?\DateTime
    {
        return null !== $value && '' !== $value ? new \DateTime($value) : null;
    }

    private function computeEndTime(?\DateTime $start, ?string $duration): ?\DateTime
    {
        if (null === $start) {
            return null;
        }

        $end = clone $start;
        if (null === $duration || '' === trim($duration)) {
            return $end;
        }

        $duration = trim(strtolower($duration));
        $minutes = 0;

        if (preg_match('/^(\d{1,2}):(\d{2})$/', $duration, $matches)) {
            $minutes = ((int) $matches[1] * 60) + (int) $matches[2];
        } elseif (preg_match('/^(\d+)h(?:\s*(\d+))?$/', $duration, $matches)) {
            $minutes = ((int) $matches[1] * 60) + (isset($matches[2]) ? (int) $matches[2] : 0);
        } elseif (ctype_digit($duration)) {
            $minutes = (int) $duration;
        }

        if ($minutes > 0) {
            $end->modify(sprintf('+%d minutes', $minutes));
        }

        return $end;
    }

    private function uuidFromHex(?string $hex): ?Uuid
    {
        if (null === $hex || 32 !== strlen($hex)) {
            return null;
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
