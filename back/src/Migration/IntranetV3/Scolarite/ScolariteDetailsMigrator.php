<?php

namespace App\Migration\IntranetV3\Scolarite;

use App\Entity\Etudiant\EtudiantScolariteSemestre;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructurePn;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Users\Etudiant;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\Contract\MigratorInterface;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;

final class ScolariteDetailsMigrator extends AbstractMigrator
{
    private const MAX_DIAGNOSTIC_SAMPLES = 20;

    public function getName(): string
    {
        return 'scolarite-details';
    }

    /** @return list<class-string<MigratorInterface>> */
    public function getDependencies(): array
    {
        return [ScolariteMigrator::class];
    }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;
        $processed = 0;
        $messages = [];
        $sampleCount = 0;
        $propositionsNonMigrees = 0;

        $sql = <<<'SQL'
SELECT
    sc.id,
    sc.etudiant_id,
    sc.semestre_id,
    sc.annee_universitaire_id,
    a.diplome_id,
    sc.decision,
    sc.proposition,
    sc.moyennes_matieres,
    sc.moyennes_ues,
    sc.rang
FROM scolarite sc
INNER JOIN semestre s ON s.id = sc.semestre_id
INNER JOIN annee a ON a.id = s.annee_id
ORDER BY sc.id
SQL;

        $etudiantRepository = $this->entityManager->getRepository(Etudiant::class);
        $anneeUniversitaireRepository = $this->entityManager->getRepository(StructureAnneeUniversitaire::class);
        $diplomeRepository = $this->entityManager->getRepository(StructureDiplome::class);
        $pnRepository = $this->entityManager->getRepository(StructurePn::class);
        $scolariteSemestreRepository = $this->entityManager->getRepository(EtudiantScolariteSemestre::class);

        foreach ($this->source->executeQuery($sql)->iterateAssociative() as $row) {
            try {
                if (null === $row['annee_universitaire_id']) {
                    ++$skipped;
                    ++$processed;
                    $this->flushBatch($context, $processed);
                    continue;
                }

                $etudiant = $etudiantRepository->findOneBy(['oldId' => (int) $row['etudiant_id']]);
                $anneeUniversitaire = $anneeUniversitaireRepository->findOneBy(['oldId' => (int) $row['annee_universitaire_id']]);
                $diplome = $diplomeRepository->findOneBy(['oldId' => (int) $row['diplome_id']]);

                if (null === $etudiant || null === $anneeUniversitaire || null === $diplome) {
                    ++$skipped;
                    $this->addSample($messages, $sampleCount, sprintf(
                        'Scolarite V3 #%s ignorée: étudiant, année universitaire ou diplôme non résolu.',
                        $row['id'],
                    ));
                    ++$processed;
                    $this->flushBatch($context, $processed);
                    continue;
                }

                $pn = $pnRepository->findOneBy([
                    'diplome' => $diplome,
                    'anneeUniversitaire' => $anneeUniversitaire,
                ]);

                if (null === $pn) {
                    ++$skipped;
                    $this->addSample($messages, $sampleCount, sprintf(
                        'Scolarite V3 #%s ignorée: snapshot PN non résolu.',
                        $row['id'],
                    ));
                    ++$processed;
                    $this->flushBatch($context, $processed);
                    continue;
                }

                $semestre = $this->findSnapshotSemestre((int) $row['semestre_id'], $pn);
                if (null === $semestre) {
                    ++$skipped;
                    $this->addSample($messages, $sampleCount, sprintf(
                        'Scolarite V3 #%s ignorée: semestre snapshot non résolu.',
                        $row['id'],
                    ));
                    ++$processed;
                    $this->flushBatch($context, $processed);
                    continue;
                }

                $scolariteSemestre = $this->entityManager->createQueryBuilder()
                    ->select('ss')
                    ->from(EtudiantScolariteSemestre::class, 'ss')
                    ->innerJoin('ss.scolarite', 'sc')
                    ->andWhere('sc.etudiant = :etudiant')
                    ->andWhere('sc.anneeUniversitaire = :anneeUniversitaire')
                    ->andWhere('ss.semestre = :semestre')
                    ->setParameter('etudiant', $etudiant)
                    ->setParameter('anneeUniversitaire', $anneeUniversitaire)
                    ->setParameter('semestre', $semestre)
                    ->getQuery()
                    ->getOneOrNullResult();

                if (null === $scolariteSemestre) {
                    ++$skipped;
                    $this->addSample($messages, $sampleCount, sprintf(
                        'Scolarite V3 #%s ignorée: scolarité semestrielle cible non résolue.',
                        $row['id'],
                    ));
                    ++$processed;
                    $this->flushBatch($context, $processed);
                    continue;
                }

                $scolariteSemestre->setDecision($this->mapDecision($row['decision']));
                $scolariteSemestre->setRang(null !== $row['rang'] ? (int) $row['rang'] : null);
                $scolariteSemestre->setMoyennesMatiere($this->decodeLegacyArray($row['moyennes_matieres']));
                $scolariteSemestre->setMoyennesUe($this->decodeLegacyArray($row['moyennes_ues']));

                if (null !== $row['proposition'] && '' !== trim((string) $row['proposition'])) {
                    ++$propositionsNonMigrees;
                    $this->addSample($messages, $sampleCount, sprintf(
                        'Scolarite V3 #%s: proposition "%s" non migrée automatiquement (champ libre V3, relation StructureSemestre en cible).',
                        $row['id'],
                        (string) $row['proposition'],
                    ));
                }

                ++$updated;
            } catch (\Throwable $e) {
                ++$failed;
                $this->addSample($messages, $sampleCount, sprintf(
                    'Scolarite V3 #%s: %s',
                    $row['id'],
                    $e->getMessage(),
                ));
            }

            ++$processed;
            $this->flushBatch($context, $processed);
        }

        $this->flushAndClear($context);

        if ($propositionsNonMigrees > 0) {
            $messages[] = sprintf(
                'Propositions V3 non migrées automatiquement: %d. Une règle de résolution explicite vers StructureSemestre reste à définir.',
                $propositionsNonMigrees,
            );
        }

        return new MigrationResult($created, $updated, $skipped, $failed, $messages);
    }

    private function findSnapshotSemestre(int $oldId, StructurePn $pn): ?StructureSemestre
    {
        return $this->entityManager->createQueryBuilder()
            ->select('s')
            ->from(StructureSemestre::class, 's')
            ->innerJoin('s.annee', 'a')
            ->andWhere('s.oldId = :oldId')
            ->andWhere('a.pn = :pn')
            ->setParameter('oldId', $oldId)
            ->setParameter('pn', $pn)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function mapDecision(mixed $decision): ?bool
    {
        return match ((string) $decision) {
            'V', 'VCJ', 'VCA' => true,
            'NV', 'DEF' => false,
            default => null,
        };
    }

    private function decodeLegacyArray(mixed $value): ?array
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        $decoded = @unserialize((string) $value, ['allowed_classes' => false]);
        if (is_array($decoded)) {
            return $decoded;
        }

        try {
            $decoded = json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
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
