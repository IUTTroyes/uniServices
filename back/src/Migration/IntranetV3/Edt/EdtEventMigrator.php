<?php

namespace App\Migration\IntranetV3\Edt;

use App\Entity\Edt\EdtEvent;
use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureGroupe;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Users\Personnel;
use App\Enum\TypeEnseignementEnum;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\Apc\ApcRelationMigrator;
use App\Migration\IntranetV3\Contract\MigratorInterface;
use App\Migration\IntranetV3\Maquette\MatiereMigrator;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;
use App\Migration\IntranetV3\Structure\AnneeUniversitaireMigrator;
use App\Migration\IntranetV3\Structure\GroupeMigrator;
use App\Migration\IntranetV3\Users\PersonnelMigrator;

final class EdtEventMigrator extends AbstractMigrator
{
    private const MAX_DIAGNOSTIC_SAMPLES = 20;

    public function getName(): string
    {
        return 'edt-actif';
    }

    /** @return list<class-string<MigratorInterface>> */
    public function getDependencies(): array
    {
        return [
            MatiereMigrator::class,
            ApcRelationMigrator::class,
            GroupeMigrator::class,
            PersonnelMigrator::class,
            AnneeUniversitaireMigrator::class,
        ];
    }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $created = $updated = $skipped = $failed = $processed = 0;
        $messages = [];
        $sampleCount = 0;
        $diagnostics = [
            'annee' => 0,
            'semestre' => 0,
            'enseignement' => 0,
        ];

        $sql = <<<'SQL'
SELECT
    ep.id,
    ep.jour,
    ep.salle,
    ep.ordre,
    ep.debut,
    ep.fin,
    ep.semaine,
    ep.evaluation,
    ep.semestre_id,
    ep.intervenant_id,
    ep.texte,
    ep.groupe,
    ep.type,
    ep.commentaire,
    ep.date,
    ep.annee_universitaire_id,
    ep.ordre_semestre,
    ep.diplome_id,
    ep.id_edu_sign,
    ep.heure_debut,
    ep.heure_fin,
    ep.groupe_objet_id,
    ep.type_matiere,
    ep.id_matiere
FROM edt_planning ep
INNER JOIN annee_universitaire au ON au.id = ep.annee_universitaire_id
WHERE au.active = 1
ORDER BY ep.id
SQL;

        foreach ($this->source->executeQuery($sql)->iterateAssociative() as $row) {
            try {
                $anneeUniversitaire = $this->entityManager->getRepository(StructureAnneeUniversitaire::class)
                    ->findOneBy(['oldId' => (int) $row['annee_universitaire_id'], 'actif' => true]);

                if (null === $anneeUniversitaire) {
                    ++$skipped;
                    ++$diagnostics['annee'];
                    $this->addSample($messages, $sampleCount, sprintf('EDT V3 #%s ignoré: année universitaire active non résolue.', $row['id']));
                    ++$processed;
                    $this->flushBatch($context, $processed);
                    continue;
                }

                $semestre = $this->findSnapshotSemestre((int) $row['semestre_id'], $anneeUniversitaire);
                if (null === $semestre) {
                    ++$skipped;
                    ++$diagnostics['semestre'];
                    $this->addSample($messages, $sampleCount, sprintf('EDT V3 #%s ignoré: semestre V3 #%s non résolu.', $row['id'], $row['semestre_id']));
                    ++$processed;
                    $this->flushBatch($context, $processed);
                    continue;
                }

                $enseignement = null;
                if (!empty($row['type_matiere']) && !empty($row['id_matiere'])) {
                    $type = TypeEnseignementEnum::tryFrom(strtolower((string) $row['type_matiere']));
                    if (null !== $type) {
                        $enseignement = $this->findSnapshotEnseignement((int) $row['id_matiere'], $type, $semestre);
                    }
                }

                // Les entrées de planning libres (réunion, atelier, etc.) n'ont pas nécessairement
                // d'enseignement. On les conserve. Seules les références explicites non résolues
                // sont comptabilisées comme diagnostic.
                if (!empty($row['id_matiere']) && null === $enseignement) {
                    ++$diagnostics['enseignement'];
                }

                $event = $this->entityManager->getRepository(EdtEvent::class)->findOneBy(['oldId' => (int) $row['id']]);
                $isNew = null === $event;
                $event ??= new EdtEvent();

                $personnel = null !== $row['intervenant_id']
                    ? $this->entityManager->getRepository(Personnel::class)->findOneBy(['oldId' => (int) $row['intervenant_id']])
                    : null;

                $groupe = null !== $row['groupe_objet_id']
                    ? $this->findSnapshotGroup((int) $row['groupe_objet_id'], $semestre)
                    : null;

                $date = !empty($row['date']) ? new \DateTime((string) $row['date']) : null;
                $debut = $this->toTime($row['heure_debut'] ?? null);
                $fin = $this->toTime($row['heure_fin'] ?? null);

                $event
                    ->setOldId((int) $row['id'])
                    ->setSemaineFormation(null !== $row['semaine'] ? (int) $row['semaine'] : null)
                    ->setJour(null !== $row['jour'] ? (int) $row['jour'] : null)
                    ->setDate($date)
                    ->setDebut($debut)
                    ->setFin($fin)
                    ->setSalle((string) ($row['salle'] ?: '-'))
                    ->setPersonnel($personnel)
                    ->setEnseignement($enseignement)
                    ->setGroupe($groupe)
                    ->setType($row['type'] ?: null)
                    ->setAnneeUniversitaire($anneeUniversitaire)
                    ->setSemestre($semestre)
                    ->setEvaluation((bool) $row['evaluation'])
                    ->setAPlacer(false)
                    ->setOrdreSeance(null !== $row['ordre'] && '' !== (string) $row['ordre'] ? (int) $row['ordre'] : null)
                    ->setLibModule($enseignement?->getLibelle() ?? ($row['texte'] ?: null))
                    ->setCodeModule($enseignement?->getCodeEnseignement())
                    ->setLibPersonnel($personnel ? trim(($personnel->getPrenom() ?? '') . ' ' . ($personnel->getNom() ?? '')) : null)
                    ->setCodeGroupe($groupe?->getLibelle())
                    ->setLibGroupe($groupe?->getLibelle());

                if (method_exists($event, 'setIdEduSign')) {
                    $event->setIdEduSign($row['id_edu_sign'] ?: null);
                }

                if ($isNew) {
                    $this->entityManager->persist($event);
                    ++$created;
                } else {
                    ++$updated;
                }
            } catch (\Throwable $e) {
                ++$failed;
                $this->addSample($messages, $sampleCount, sprintf('EDT V3 #%s: %s', $row['id'], $e->getMessage()));
            }

            ++$processed;
            $this->flushBatch($context, $processed);
        }

        $this->flushAndClear($context);

        if (array_sum($diagnostics) > 0) {
            $messages[] = sprintf(
                'Résumé EDT actif: années non résolues=%d, semestres=%d, enseignements explicites non résolus=%d.',
                $diagnostics['annee'],
                $diagnostics['semestre'],
                $diagnostics['enseignement'],
            );
        }

        return new MigrationResult($created, $updated, $skipped, $failed, $messages);
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

    private function findSnapshotEnseignement(int $oldId, TypeEnseignementEnum $type, StructureSemestre $semestre): ?ScolEnseignement
    {
        return $this->entityManager->createQueryBuilder()
            ->select('e')
            ->from(ScolEnseignement::class, 'e')
            ->innerJoin('e.enseignementUes', 'eu')
            ->innerJoin('eu.ue', 'ue')
            ->andWhere('e.oldId = :oldId')
            ->andWhere('e.type = :type')
            ->andWhere('ue.semestre = :semestre')
            ->setParameter('oldId', $oldId)
            ->setParameter('type', $type)
            ->setParameter('semestre', $semestre)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function findSnapshotGroup(int $oldId, StructureSemestre $semestre): ?StructureGroupe
    {
        return $this->entityManager->createQueryBuilder()
            ->select('g')
            ->from(StructureGroupe::class, 'g')
            ->innerJoin('g.semestres', 's')
            ->andWhere('g.oldId = :oldId')
            ->andWhere('s = :semestre')
            ->setParameter('oldId', $oldId)
            ->setParameter('semestre', $semestre)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function toTime(mixed $value): ?\DateTime
    {
        if (null === $value || '' === (string) $value) {
            return null;
        }

        return new \DateTime((string) $value);
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
