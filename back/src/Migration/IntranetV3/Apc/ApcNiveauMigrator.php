<?php

namespace App\Migration\IntranetV3\Apc;

use App\Entity\Apc\ApcCompetence;
use App\Entity\Apc\ApcNiveau;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\Contract\MigratorInterface;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;

final class ApcNiveauMigrator extends AbstractMigrator
{
    public function getName(): string
    {
        return 'apc-niveaux';
    }

    /** @return list<class-string<MigratorInterface>> */
    public function getDependencies(): array
    {
        return [ApcCompetenceMigrator::class];
    }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $created = $updated = $skipped = $failed = $processed = 0;
        $messages = [];

        $sql = <<<'SQL'
SELECT id, competence_id, libelle, ordre, ordre_annee
FROM apc_niveau
ORDER BY competence_id, ordre, id
SQL;

        foreach ($this->source->executeQuery($sql)->iterateAssociative() as $row) {
            try {
                $competence = $this->entityManager->getRepository(ApcCompetence::class)
                    ->findOneBy(['oldId' => (int) $row['competence_id']]);

                if (null === $competence) {
                    ++$skipped;
                    if (count($messages) < 20) {
                        $messages[] = sprintf('Niveau APC V3 #%s ignoré: compétence V3 #%s non résolue.', $row['id'], $row['competence_id']);
                    }
                    ++$processed;
                    $this->flushBatch($context, $processed);
                    continue;
                }

                $entity = $this->entityManager->getRepository(ApcNiveau::class)->findOneBy([
                    'competence' => $competence,
                    'ordre' => (int) $row['ordre'],
                ]);
                $isNew = null === $entity;
                $entity ??= new ApcNiveau($competence);

                $entity
                    ->setCompetence($competence)
                    ->setLibelle((string) $row['libelle'])
                    ->setOrdre((int) $row['ordre'])
                    ->setOrdreAnnee(null !== $row['ordre_annee'] ? (int) $row['ordre_annee'] : null);

                if ($isNew) {
                    $this->entityManager->persist($entity);
                    ++$created;
                } else {
                    ++$updated;
                }
            } catch (\Throwable $e) {
                ++$failed;
                if (count($messages) < 20) {
                    $messages[] = sprintf('Niveau APC V3 #%s: %s', $row['id'], $e->getMessage());
                }
            }

            ++$processed;
            $this->flushBatch($context, $processed);
        }

        $this->flushAndClear($context);

        return new MigrationResult($created, $updated, $skipped, $failed, $messages);
    }
}
