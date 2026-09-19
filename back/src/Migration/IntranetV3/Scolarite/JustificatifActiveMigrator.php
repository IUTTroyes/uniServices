<?php

namespace App\Migration\IntranetV3\Scolarite;

use App\Entity\Etudiant\EtudiantScolariteSemestre;
use App\Entity\Users\Etudiant;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\Contract\MigratorInterface;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;
use IntranetBundle\Entity\Etudiant\EtudiantAbsence;
use IntranetBundle\Entity\Etudiant\EtudiantAbsenceJustificatif;
use IntranetBundle\Enum\EtatJustificatifEnum;
use Symfony\Component\Uid\Uuid;

final class JustificatifActiveMigrator extends AbstractMigrator
{
    private const MAX_DIAGNOSTIC_SAMPLES = 20;

    public function getName(): string
    {
        return 'justificatifs-actifs';
    }

    /** @return list<class-string<MigratorInterface>> */
    public function getDependencies(): array
    {
        return [AbsenceActiveMigrator::class];
    }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $created = $updated = $skipped = $failed = $processed = 0;
        $messages = [];
        $sampleCount = 0;
        $linkedAbsences = 0;
        $diagnostics = [
            'etudiant' => 0,
            'scolariteSemestre' => 0,
        ];

        $sql = <<<'SQL'
SELECT
    j.id,
    HEX(j.uuid) AS uuid_hex,
    j.date_heure_debut,
    j.date_heure_fin,
    j.motif,
    j.etat,
    j.etudiant_id,
    j.fichier_name,
    j.annee_universitaire_id,
    j.semestre_id
FROM absence_justificatif j
INNER JOIN annee_universitaire au ON au.id = j.annee_universitaire_id
WHERE au.active = 1
ORDER BY j.id
SQL;

        foreach ($this->source->executeQuery($sql)->iterateAssociative() as $row) {
            try {
                $etudiant = $this->entityManager->getRepository(Etudiant::class)
                    ->findOneBy(['oldId' => (int) $row['etudiant_id']]);
                if (null === $etudiant) {
                    ++$skipped;
                    ++$diagnostics['etudiant'];
                    $this->addSample($messages, $sampleCount, sprintf('Justificatif V3 #%s ignoré: étudiant non résolu.', $row['id']));
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
                    $this->addSample($messages, $sampleCount, sprintf('Justificatif V3 #%s ignoré: scolarité semestrielle active non résolue.', $row['id']));
                    ++$processed;
                    $this->flushBatch($context, $processed);
                    continue;
                }

                $uuid = $this->uuidFromHex($row['uuid_hex']);
                $justificatif = $this->entityManager->getRepository(EtudiantAbsenceJustificatif::class)
                    ->findOneBy(['uuid' => $uuid]);
                $isNew = null === $justificatif;
                $justificatif ??= new EtudiantAbsenceJustificatif();
                $justificatif->setUuid($uuid);

                $debut = new \DateTime((string) $row['date_heure_debut']);
                $fin = new \DateTime((string) $row['date_heure_fin']);
                $justificatif->setDebut($debut);
                $justificatif->setFin($fin);
                $justificatif
                    ->setMotif($row['motif'] ?: null)
                    ->setEtat($this->mapEtat((string) $row['etat']))
                    ->setScolariteSemestre($scolariteSemestre);
                $justificatif->setFichier($row['fichier_name'] ?: null);

                if ($isNew) {
                    $this->entityManager->persist($justificatif);
                    ++$created;
                } else {
                    ++$updated;
                }

                foreach ($this->entityManager->getRepository(EtudiantAbsence::class)->findBy([
                    'scolariteSemestre' => $scolariteSemestre,
                ]) as $absence) {
                    $event = $absence->getEvent();
                    if (null === $event?->getDate() || null === $event->getDebut()) {
                        continue;
                    }

                    $eventDebut = $this->combineDateTime($event->getDate(), $event->getDebut());
                    $eventFin = null !== $event->getFin()
                        ? $this->combineDateTime($event->getDate(), $event->getFin())
                        : clone $eventDebut;

                    if ($justificatif->couvre($eventDebut, $eventFin)) {
                        if ($absence->getAbsenceJustificatif() !== $justificatif) {
                            $absence->setAbsenceJustificatif($justificatif);
                            $absence->setDateJustification($justificatif->getEtat() === EtatJustificatifEnum::VALIDE ? $fin : null);
                            ++$linkedAbsences;
                        }
                    }
                }
            } catch (\Throwable $e) {
                ++$failed;
                $this->addSample($messages, $sampleCount, sprintf('Justificatif V3 #%s: %s', $row['id'], $e->getMessage()));
            }

            ++$processed;
            $this->flushBatch($context, $processed);
        }

        $this->flushAndClear($context);

        if (array_sum($diagnostics) > 0) {
            $messages[] = sprintf(
                'Résumé justificatifs actifs non migrés: étudiants=%d, scolarités semestrielles=%d.',
                $diagnostics['etudiant'],
                $diagnostics['scolariteSemestre'],
            );
        }
        $messages[] = sprintf('Absences actives rattachées à un justificatif: %d.', $linkedAbsences);

        return new MigrationResult($created, $updated, $skipped, $failed, $messages);
    }

    private function mapEtat(string $etat): EtatJustificatifEnum
    {
        return match (strtoupper($etat)) {
            'A' => EtatJustificatifEnum::VALIDE,
            'R' => EtatJustificatifEnum::REFUSE,
            default => EtatJustificatifEnum::EN_ATTENTE,
        };
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

    private function combineDateTime(\DateTimeInterface $date, \DateTimeInterface $time): \DateTime
    {
        return new \DateTime($date->format('Y-m-d') . ' ' . $time->format('H:i:s'));
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
