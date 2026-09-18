<?php

namespace App\Migration\IntranetV3\Scolarite;

use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Scolarite\ScolEvaluation;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Users\Personnel;
use App\Enum\EtatEvaluationEnum;
use App\Enum\TypeEnseignementEnum;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\Apc\ApcRessourceMigrator;
use App\Migration\IntranetV3\Apc\ApcSaeMigrator;
use App\Migration\IntranetV3\Contract\MigratorInterface;
use App\Migration\IntranetV3\Maquette\MatiereMigrator;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;
use App\Migration\IntranetV3\Structure\AnneeUniversitaireMigrator;
use App\Migration\IntranetV3\Users\PersonnelMigrator;
use Symfony\Component\Uid\Uuid;

final class EvaluationMigrator extends AbstractMigrator
{
    private const MAX_DIAGNOSTIC_SAMPLES = 20;

    public function getName(): string
    {
        return 'evaluations';
    }

    /** @return list<class-string<MigratorInterface>> */
    public function getDependencies(): array
    {
        return [
            MatiereMigrator::class,
            ApcRessourceMigrator::class,
            ApcSaeMigrator::class,
            AnneeUniversitaireMigrator::class,
            PersonnelMigrator::class,
        ];
    }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $created = $updated = $skipped = $failed = $processed = 0;
        $messages = [];
        $diagnostics = [
            'typeMatiere' => 0,
            'anneeUniversitaire' => 0,
            'semestre' => 0,
            'enseignement' => 0,
        ];
        $sampleCount = 0;

        $sql = <<<'SQL'
SELECT
    e.id,
    HEX(e.uuid) AS uuid_hex,
    e.personnel_auteur_id,
    e.annee_universitaire_id,
    e.semestre_id,
    e.parent_id,
    e.type_groupe,
    e.type_matiere,
    e.id_matiere,
    e.date_evaluation,
    e.visible,
    e.modifiable,
    e.coefficient,
    e.commentaire,
    e.libelle,
    HEX(parent.uuid) AS parent_uuid_hex
FROM evaluation e
LEFT JOIN evaluation parent ON parent.id = e.parent_id
ORDER BY e.id
SQL;

        foreach ($this->source->executeQuery($sql)->iterateAssociative() as $row) {
            try {
                $enseignementType = $this->mapEnseignementType((string) $row['type_matiere']);
                if (null === $enseignementType) {
                    ++$skipped;
                    ++$diagnostics['typeMatiere'];
                    ++$processed;
                    $this->flushBatch($context, $processed);
                    continue;
                }

                $uuid = $this->uuidFromHex($row['uuid_hex']);
                $anneeUniversitaire = null !== $row['annee_universitaire_id']
                    ? $this->entityManager->getRepository(StructureAnneeUniversitaire::class)
                        ->findOneBy(['oldId' => (int) $row['annee_universitaire_id']])
                    : null;

                if (null === $anneeUniversitaire) {
                    ++$skipped;
                    ++$diagnostics['anneeUniversitaire'];
                    $this->addSample($messages, $sampleCount, sprintf(
                        'Evaluation V3 #%s ignorée: année universitaire #%s non résolue.',
                        $row['id'],
                        $row['annee_universitaire_id'] ?? 'NULL',
                    ));
                    ++$processed;
                    $this->flushBatch($context, $processed);
                    continue;
                }

                $semestre = $this->findSnapshotSemestre((int) $row['semestre_id'], $anneeUniversitaire);
                if (null === $semestre) {
                    ++$skipped;
                    ++$diagnostics['semestre'];
                    $this->addSample($messages, $sampleCount, sprintf(
                        'Evaluation V3 #%s ignorée: semestre V3 #%s non résolu dans le snapshot de l\'année #%s.',
                        $row['id'],
                        $row['semestre_id'],
                        $row['annee_universitaire_id'],
                    ));
                    ++$processed;
                    $this->flushBatch($context, $processed);
                    continue;
                }

                $enseignement = $this->findSnapshotEnseignement((int) $row['id_matiere'], $semestre, $enseignementType);
                if (null === $enseignement) {
                    ++$skipped;
                    ++$diagnostics['enseignement'];
                    $this->addSample($messages, $sampleCount, sprintf(
                        'Evaluation V3 #%s ignorée: enseignement %s V3 #%s non résolu dans le semestre snapshot.',
                        $row['id'],
                        $row['type_matiere'],
                        $row['id_matiere'],
                    ));
                    ++$processed;
                    $this->flushBatch($context, $processed);
                    continue;
                }

                $evaluation = $this->entityManager->getRepository(ScolEvaluation::class)
                    ->findOneBy(['uuid' => $uuid]);
                $isNew = null === $evaluation;
                $evaluation ??= new ScolEvaluation();

                $evaluation->setUuid($uuid);
                $evaluation
                    ->setLibelle($row['libelle'] ?: sprintf('Evaluation #%s', $row['id']))
                    ->setCommentaire($row['commentaire'] ?: null)
                    ->setCoeff(null !== $row['coefficient'] ? (float) $row['coefficient'] : null)
                    ->setDate(null !== $row['date_evaluation'] ? new \DateTime((string) $row['date_evaluation']) : null)
                    ->setVisible((bool) $row['visible'])
                    ->setModifiable((bool) $row['modifiable'])
                    ->setTypeGroupe($row['type_groupe'] ?: null)
                    ->setAnneeUniversitaire($anneeUniversitaire)
                    ->setSemestre($semestre)
                    ->setEnseignement($enseignement)
                    ->setEtat((bool) $row['visible'] ? EtatEvaluationEnum::ETAT_PUBLIEE : EtatEvaluationEnum::ETAT_INITIALISEE);

                if (null !== $row['personnel_auteur_id']) {
                    $auteur = $this->entityManager->getRepository(Personnel::class)
                        ->findOneBy(['oldId' => (int) $row['personnel_auteur_id']]);
                    if (null !== $auteur) {
                        $evaluation->addPersonnelAutorise($auteur);
                    }
                }

                if ($isNew) {
                    $this->entityManager->persist($evaluation);
                    ++$created;
                } else {
                    ++$updated;
                }
            } catch (\Throwable $e) {
                ++$failed;
                $this->addSample($messages, $sampleCount, sprintf(
                    'Evaluation V3 #%s: %s',
                    $row['id'],
                    $e->getMessage(),
                ));
            }

            ++$processed;
            $this->flushBatch($context, $processed);
        }

        $this->flushAndClear($context);

        $parentSql = <<<'SQL'
SELECT HEX(e.uuid) AS uuid_hex, HEX(parent.uuid) AS parent_uuid_hex
FROM evaluation e
INNER JOIN evaluation parent ON parent.id = e.parent_id
WHERE e.type_matiere IN ('matiere', 'ressource', 'sae')
SQL;

        foreach ($this->source->executeQuery($parentSql)->iterateAssociative() as $row) {
            try {
                $evaluation = $this->entityManager->getRepository(ScolEvaluation::class)
                    ->findOneBy(['uuid' => $this->uuidFromHex($row['uuid_hex'])]);
                $parent = $this->entityManager->getRepository(ScolEvaluation::class)
                    ->findOneBy(['uuid' => $this->uuidFromHex($row['parent_uuid_hex'])]);

                if (null !== $evaluation && null !== $parent) {
                    $evaluation->setParent($parent);
                }
            } catch (\Throwable) {
                // Une relation parent invalide ne doit pas bloquer les évaluations déjà migrées.
            }
        }

        $this->flushAndClear($context);

        if (array_sum($diagnostics) > 0) {
            $messages[] = sprintf(
                'Résumé évaluations non migrées: types non supportés=%d, années universitaires=%d, semestres=%d, enseignements=%d.',
                $diagnostics['typeMatiere'],
                $diagnostics['anneeUniversitaire'],
                $diagnostics['semestre'],
                $diagnostics['enseignement'],
            );
        }

        return new MigrationResult($created, $updated, $skipped, $failed, $messages);
    }

    private function findSnapshotSemestre(int $oldId, StructureAnneeUniversitaire $anneeUniversitaire): ?StructureSemestre
    {
        return $this->entityManager->createQueryBuilder()
            ->select('sem')
            ->from(StructureSemestre::class, 'sem')
            ->innerJoin('sem.annee', 'an')
            ->innerJoin('an.pn', 'pn')
            ->andWhere('sem.oldId = :oldId')
            ->andWhere('pn.anneeUniversitaire = :anneeUniversitaire')
            ->setParameter('oldId', $oldId)
            ->setParameter('anneeUniversitaire', $anneeUniversitaire)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function findSnapshotEnseignement(
        int $oldId,
        StructureSemestre $semestre,
        TypeEnseignementEnum $type,
    ): ?ScolEnseignement {
        return $this->entityManager->createQueryBuilder()
            ->select('ens')
            ->from(ScolEnseignement::class, 'ens')
            ->innerJoin('ens.enseignementUes', 'ensUe')
            ->innerJoin('ensUe.ue', 'ue')
            ->andWhere('ens.oldId = :oldId')
            ->andWhere('ens.type = :type')
            ->andWhere('ue.semestre = :semestre')
            ->setParameter('oldId', $oldId)
            ->setParameter('type', $type)
            ->setParameter('semestre', $semestre)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function mapEnseignementType(string $type): ?TypeEnseignementEnum
    {
        return match ($type) {
            'matiere' => TypeEnseignementEnum::TYPE_MATIERE,
            'ressource' => TypeEnseignementEnum::TYPE_RESSOURCE,
            'sae' => TypeEnseignementEnum::TYPE_SAE,
            default => null,
        };
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
