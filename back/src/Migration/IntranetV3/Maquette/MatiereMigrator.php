<?php

namespace App\Migration\IntranetV3\Maquette;

use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Scolarite\ScolEnseignementUe;
use App\Entity\Structure\StructureUe;
use App\Enum\TypeEnseignementEnum;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\Contract\MigratorInterface;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;

final class MatiereMigrator extends AbstractMigrator
{
    public function getName(): string
    {
        return 'matieres';
    }

    /** @return list<class-string<MigratorInterface>> */
    public function getDependencies(): array
    {
        return [UeMigrator::class];
    }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $created = $updated = $skipped = $failed = 0;
        $messages = [];

        foreach ($this->entityManager->getRepository(StructureUe::class)->findAll() as $ue) {
            $ueOldId = $ue->getOldId();
            if (null === $ueOldId) {
                ++$skipped;
                continue;
            }

            $sql = <<<'SQL'
SELECT
    id, ue_id, matiere_parent_id, libelle, libelle_court, description,
    cm_ppn, td_ppn, tp_ppn, cm_formation, td_formation, tp_formation,
    code_matiere, code_element, nb_notes, suspendu, mutualisee,
    coefficient, nb_ects, objectifs_module, competences_visees, contenu, pre_requis, modalites, prolongements, mots_cles, pac, ppn_id, parcours_id
FROM matiere
WHERE ue_id = :ue_id
ORDER BY id
SQL;

            $rows = $this->source->fetchAllAssociative($sql, ['ue_id' => $ueOldId]);
            $snapshot = [];

            foreach ($rows as $row) {
                try {
                    $enseignement = $this->findSnapshotEnseignement((int) $row['id'], $ue);
                    $isNew = null === $enseignement;
                    $enseignement ??= new ScolEnseignement();

                    $enseignement
                        ->setOldId((int) $row['id'])
                        ->setLibelle((string) $row['libelle'])
                        ->setLibelleCourt($row['libelle_court'] ?: null)
                        ->setDescription($row['description'] ?: null)
                        ->setObjectif($row['objectifs_module'] ?: null)
                        ->setPreRequis($row['pre_requis'] ?: null)
                        ->setMotsCles($row['mots_cles'] ?: null)
                        ->setCodeEnseignement($row['code_matiere'] ?: null)
                        ->setCodeApogee($row['code_element'] ?: null)
                        ->setSuspendu((bool) $row['suspendu'])
                        ->setMutualisee((bool) $row['mutualisee'])
                        ->setNbNotes((int) $row['nb_notes'])
                        ->setType(TypeEnseignementEnum::TYPE_MATIERE)
                        ->setHeures([
                            'CM' => ['PN' => (float) $row['cm_ppn'], 'IUT' => (float) $row['cm_formation']],
                            'TD' => ['PN' => (float) $row['td_ppn'], 'IUT' => (float) $row['td_formation']],
                            'TP' => ['PN' => (float) $row['tp_ppn'], 'IUT' => (float) $row['tp_formation']],
                            'Projet' => ['PN' => 0, 'IUT' => 0],
                        ])
                        ->setOpt([
                            'competences_visees' => $row['competences_visees'] ?: null,
                            'contenu' => $row['contenu'] ?: null,
                            'modalites' => $row['modalites'] ?: null,
                            'prolongements' => $row['prolongements'] ?: null,
                            'pac' => (bool) $row['pac'],
                            'ppn_old_id' => null !== $row['ppn_id'] ? (int) $row['ppn_id'] : null,
                            'parcours_old_id' => null !== $row['parcours_id'] ? (int) $row['parcours_id'] : null,
                        ]);

                    if ($isNew) {
                        $this->entityManager->persist($enseignement);
                        ++$created;
                    } else {
                        ++$updated;
                    }

                    $link = $this->entityManager->getRepository(ScolEnseignementUe::class)->findOneBy([
                        'enseignement' => $enseignement,
                        'ue' => $ue,
                    ]);
                    if (null === $link) {
                        $link = new ScolEnseignementUe($enseignement, $ue);
                        $this->entityManager->persist($link);
                    }
                    $link
                        ->setCoefficient((float) $row['coefficient'])
                        ->setEcts((float) $row['nb_ects']);

                    $snapshot[(int) $row['id']] = $enseignement;
                } catch (\Throwable $e) {
                    ++$failed;
                    if (count($messages) < 20) {
                        $messages[] = sprintf('Matière V3 #%s / UE V3 #%s: %s', $row['id'], $ueOldId, $e->getMessage());
                    }
                }
            }

            // Les relations parent/enfant sont résolues dans le même snapshot annuel.
            foreach ($rows as $row) {
                if (null === $row['matiere_parent_id']) {
                    continue;
                }

                $child = $snapshot[(int) $row['id']] ?? null;
                $parent = $snapshot[(int) $row['matiere_parent_id']] ?? $this->findSnapshotEnseignement((int) $row['matiere_parent_id'], $ue);
                if (null !== $child && null !== $parent) {
                    $child->setParent($parent);
                }
            }

            $this->flush($context);
        }

        return new MigrationResult($created, $updated, $skipped, $failed, $messages);
    }

    private function findSnapshotEnseignement(int $oldId, StructureUe $ue): ?ScolEnseignement
    {
        return $this->entityManager->createQueryBuilder()
            ->select('e')
            ->from(ScolEnseignement::class, 'e')
            ->innerJoin('e.enseignementUes', 'eu')
            ->andWhere('e.oldId = :oldId')
            ->andWhere('eu.ue = :ue')
            ->setParameter('oldId', $oldId)
            ->setParameter('ue', $ue)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
