<?php

namespace App\Migration\IntranetV3\Apc;

use App\Entity\Apc\ApcApprentissageCritique;
use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Scolarite\ScolEnseignementUe;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Structure\StructureUe;
use App\Enum\TypeEnseignementEnum;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\Contract\MigratorInterface;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;

final class ApcRessourceMigrator extends AbstractMigrator
{
    public function getName(): string
    {
        return 'apc-ressources';
    }

    /** @return list<class-string<MigratorInterface>> */
    public function getDependencies(): array
    {
        return [ApcApprentissageCritiqueMigrator::class, ApcUeLinkMigrator::class, ApcDiplomeLinkMigrator::class];
    }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $created = $updated = $skipped = $failed = 0;
        $messages = [];

        $joinTable = $this->resolveJoinTable(['apc_ressource_semestre', 'apc_ressource_semestre_semestre']);
        if (null === $joinTable) {
            return new MigrationResult(0, 0, 0, 1, ['Table de liaison ressource/semestre V3 introuvable.']);
        }

        foreach ($this->entityManager->getRepository(StructureSemestre::class)->findAll() as $semestre) {
            $semestreOldId = $semestre->getOldId();
            if (null === $semestreOldId) {
                continue;
            }

            $sql = sprintf(<<<'SQL'
SELECT
    r.id, r.libelle, r.libelle_court, r.description,
    r.cm_ppn, r.td_ppn, r.tp_ppn, r.cm_formation, r.td_formation, r.tp_formation,
    r.code_matiere, r.code_element, r.nb_notes, r.suspendu, r.mutualisee,
    r.pre_requis, r.mots_cles, r.ressource_parent, r.has_coefficient_different
FROM apc_ressource r
INNER JOIN %s rs ON rs.apc_ressource_id = r.id
WHERE rs.semestre_id = :semestre_id
ORDER BY r.id
SQL, $joinTable);

            foreach ($this->source->executeQuery($sql, ['semestre_id' => $semestreOldId])->iterateAssociative() as $row) {
                try {
                    $enseignement = $this->findSnapshotEnseignement((int) $row['id'], $semestre);
                    $isNew = null === $enseignement;
                    $enseignement ??= new ScolEnseignement();

                    $enseignement
                        ->setOldId((int) $row['id'])
                        ->setLibelle((string) $row['libelle'])
                        ->setLibelleCourt($row['libelle_court'] ?: null)
                        ->setDescription($row['description'] ?: null)
                        ->setPreRequis($row['pre_requis'] ?: null)
                        ->setMotsCles($row['mots_cles'] ?: null)
                        ->setCodeEnseignement($row['code_matiere'] ?: null)
                        ->setCodeApogee($row['code_element'] ?: null)
                        ->setSuspendu((bool) $row['suspendu'])
                        ->setMutualisee((bool) $row['mutualisee'])
                        ->setNbNotes((int) $row['nb_notes'])
                        ->setType(TypeEnseignementEnum::TYPE_RESSOURCE)
                        ->setHeures([
                            'CM' => ['PN' => (float) $row['cm_ppn'], 'IUT' => (float) $row['cm_formation']],
                            'TD' => ['PN' => (float) $row['td_ppn'], 'IUT' => (float) $row['td_formation']],
                            'TP' => ['PN' => (float) $row['tp_ppn'], 'IUT' => (float) $row['tp_formation']],
                            'Projet' => ['PN' => 0, 'IUT' => 0],
                        ])
                        ->setOpt([
                            'ressource_parent' => (bool) $row['ressource_parent'],
                            'has_coefficient_different' => (bool) $row['has_coefficient_different'],
                        ]);

                    if ($isNew) {
                        $this->entityManager->persist($enseignement);
                        ++$created;
                    } else {
                        ++$updated;
                    }

                    $this->linkUes($enseignement, (int) $row['id'], $semestre);
                    $this->linkCriticalLearnings($enseignement, (int) $row['id']);
                } catch (\Throwable $e) {
                    ++$failed;
                    if (count($messages) < 20) {
                        $messages[] = sprintf('Ressource APC V3 #%s / semestre #%s: %s', $row['id'], $semestreOldId, $e->getMessage());
                    }
                }
            }

            $this->flush($context);
        }

        $this->flushAndClear($context);

        return new MigrationResult($created, $updated, $skipped, $failed, $messages);
    }

    private function findSnapshotEnseignement(int $oldId, StructureSemestre $semestre): ?ScolEnseignement
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
            ->setParameter('type', TypeEnseignementEnum::TYPE_RESSOURCE)
            ->setParameter('semestre', $semestre)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function linkUes(ScolEnseignement $enseignement, int $ressourceOldId, StructureSemestre $semestre): void
    {
        $parcoursOldId = $semestre->getAnnee()?->getPn()?->getDiplome()?->getParcours()?->getOldId();
        $rows = $this->source->fetchAllAssociative(
            'SELECT competence_id, parcours_id, coefficient FROM apc_ressource_competence WHERE ressource_id = :id ORDER BY id',
            ['id' => $ressourceOldId],
        );

        foreach ($rows as $row) {
            if (null !== $row['parcours_id'] && null !== $parcoursOldId && (int) $row['parcours_id'] !== $parcoursOldId) {
                continue;
            }

            $ue = $this->entityManager->createQueryBuilder()
                ->select('ue')
                ->from(StructureUe::class, 'ue')
                ->innerJoin('ue.competence', 'c')
                ->andWhere('ue.semestre = :semestre')
                ->andWhere('c.oldId = :competenceOldId')
                ->setParameter('semestre', $semestre)
                ->setParameter('competenceOldId', (int) $row['competence_id'])
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if (null === $ue) {
                continue;
            }

            $link = $this->entityManager->getRepository(ScolEnseignementUe::class)->findOneBy([
                'enseignement' => $enseignement,
                'ue' => $ue,
            ]);
            $link ??= new ScolEnseignementUe($enseignement, $ue);
            $link->setCoefficient((float) $row['coefficient'])->setEcts(0.0);
            $this->entityManager->persist($link);
        }
    }

    private function linkCriticalLearnings(ScolEnseignement $enseignement, int $ressourceOldId): void
    {
        $rows = $this->source->fetchAllAssociative(
            'SELECT apprentissage_critique_id FROM apc_ressource_apprentissage_critique WHERE ressource_id = :id',
            ['id' => $ressourceOldId],
        );

        foreach ($rows as $row) {
            $ac = $this->entityManager->getRepository(ApcApprentissageCritique::class)
                ->findOneBy(['oldId' => (int) $row['apprentissage_critique_id']]);
            if (null !== $ac) {
                $enseignement->addApprentissageCritique($ac);
            }
        }
    }

    /** @param list<string> $candidates */
    private function resolveJoinTable(array $candidates): ?string
    {
        $schema = $this->source->createSchemaManager();
        foreach ($candidates as $candidate) {
            if ($schema->tablesExist([$candidate])) {
                return $candidate;
            }
        }

        return null;
    }
}
