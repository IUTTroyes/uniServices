<?php

namespace App\Migration\IntranetV3\Structure;

use App\Entity\Structure\StructureAnnee;
use App\Entity\Structure\StructureSemestre;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\Contract\MigratorInterface;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;

final class SemestreMigrator extends AbstractMigrator
{
    public function getName(): string
    {
        return 'semestres';
    }

    /** @return list<class-string<MigratorInterface>> */
    public function getDependencies(): array
    {
        return [AnneeMigrator::class];
    }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $repository = $this->entityManager->getRepository(StructureSemestre::class);
        $anneeRepository = $this->entityManager->getRepository(StructureAnnee::class);
        $created = $updated = $skipped = $failed = $processed = 0;
        $sample = $this->source->fetchAssociative('SELECT * FROM semestre LIMIT 1');
        $messages = [];
        
        if ($sample) {
            fwrite(STDERR, "\nColonnes semestre V3: " . implode(', ', array_keys($sample)) . "\n");
        }

        // Chaque StructureAnnee est déjà un clone rattaché à un PN annuel.
        // On clone donc tous les semestres V3 de l'année source dans ce snapshot.
        $anneeIds = $this->entityManager->createQueryBuilder()
            ->select('a.id')
            ->from(StructureAnnee::class, 'a')
            ->orderBy('a.id', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();

        foreach ($anneeIds as $anneeId) {
            $annee = $anneeRepository->find((int) $anneeId);
            if (null === $annee) {
                continue;
            }
            $anneeOldId = $annee->getOldId();
            if (null === $anneeOldId || null === $annee->getPn()?->getAnneeUniversitaire()) {
                ++$skipped;
                continue;
            }

            $sql = <<<'SQL'
SELECT id, annee_id, libelle, ordre_annee, ordre_lmd, actif,
       nb_groupes_cm, nb_groupes_td, nb_groupes_tp, code_element,
       opt_mail_releve, opt_mail_modification_note, opt_dest_mail_releve_id, opt_dest_mail_modif_note_id,
       opt_evaluation_visible, opt_evaluation_modifiable, opt_penalite_absence,
       opt_mail_absence_resp, opt_dest_mail_absence_resp_id, opt_mail_absence_etudiant,
       opt_point_penalite_absence, opt_mail_assistante_justificatif_absence,
       opt_bilan_semestre, opt_rattrapage, opt_mail_rattrapage, id_edu_sign
FROM semestre
WHERE annee_id = :annee_id
ORDER BY ordre_annee, id
SQL;

            foreach ($this->source->iterateAssociative($sql, ['annee_id' => $anneeOldId]) as $row) {
                try {
                    $entity = $repository->findOneBy([
                        'oldId' => (int) $row['id'],
                        'annee' => $annee,
                    ]);
                    $isNew = null === $entity;
                    $entity ??= new StructureSemestre();

                    $entity
                        ->setOldId((int) $row['id'])
                        ->setAnnee($annee)
                        ->setLibelle((string) $row['libelle'])
                        ->setOrdreAnnee((int) $row['ordre_annee'])
                        ->setOrdreLmd((int) $row['ordre_lmd'])
                        ->setActif((bool) $row['actif'])
                        ->setNbGroupesCm((int) $row['nb_groupes_cm'])
                        ->setNbGroupesTd((int) $row['nb_groupes_td'])
                        ->setNbGroupesTp((int) $row['nb_groupes_tp'])
                        ->setCodeElement($row['code_element'] ?: null)
                        ->setKeyEduSign($row['id_edu_sign'] ?: null)
                        ->setOpt([
                            'mail_releve' => (bool) $row['opt_mail_releve'],
                            'mail_modif_note' => (bool) $row['opt_mail_modification_note'],
                            'dest_mail_releve' => (int) $row['opt_dest_mail_releve_id'],
                            'dest_mail_modif_note' => (int) $row['opt_dest_mail_modif_note_id'],
                            'eval_visible' => (bool) $row['opt_evaluation_visible'],
                            'eval_modif' => (bool) $row['opt_evaluation_modifiable'],
                            'penalite_absence' => (float) $row['opt_penalite_absence'],
                            'mail_absence_resp' => (bool) $row['opt_mail_absence_resp'],
                            'dest_mail_absence_resp' => (int) $row['opt_dest_mail_absence_resp_id'],
                            'mail_absence_etudiant' => (bool) $row['opt_mail_absence_etudiant'],
                            'opt_penalite_absence' => (bool) $row['opt_point_penalite_absence'],
                            'mail_assistante_justif_absence' => (bool) $row['opt_mail_assistante_justificatif_absence'],
                            'bilan_semestre' => (bool) $row['opt_bilan_semestre'],
                            'rattrapage' => (bool) $row['opt_rattrapage'],
                            'mail_rattrapage' => (int) $row['opt_mail_rattrapage'],
                        ]);

                    if ($isNew) {
                        $this->entityManager->persist($entity);
                        ++$created;
                    } else {
                        ++$updated;
                    }
                } catch (\Throwable $e) {
                    ++$failed;
                    $messages[] = sprintf(
                        'Semestre V3 #%s / année snapshot %s: %s',
                        $row['id'],
                        $annee->getId() ?? 'new',
                        $e->getMessage(),
                    );
                }

                ++$processed;

                // Flush/clear only once the complete source year has been cloned.
                // Clearing in the middle of this inner loop detaches $annee while
                // Doctrine is still processing its remaining V3 semesters.
                // As a V3 year only owns a few semesters, the parent boundary is
                // already a small and safe memory batch.
            }

            $this->flushAndClear($context);
        }

        $this->flush($context);

        return new MigrationResult($created, $updated, $skipped, $failed, $messages);
    }
}
