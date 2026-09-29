<?php

namespace App\Migration\IntranetV3\Structure;

use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureCalendrier;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\Contract\MigratorInterface;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;

final class CalendrierMigrator extends AbstractMigrator
{
    public function getName(): string
    {
        return 'calendriers';
    }

    /** @return list<class-string<MigratorInterface>> */
    public function getDependencies(): array
    {
        return [AnneeUniversitaireMigrator::class];
    }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $created = $updated = $skipped = $failed = $processed = 0;
        $messages = [];
        $calendarRepository = $this->entityManager->getRepository(StructureCalendrier::class);
        $yearRepository = $this->entityManager->getRepository(StructureAnneeUniversitaire::class);

        $sql = <<<'SQL'
SELECT c.id, c.semaine_formation, c.semaine_reelle, c.date_lundi, c.annee_universitaire_id
FROM calendrier c
ORDER BY c.annee_universitaire_id, c.semaine_formation, c.id
SQL;

        foreach ($this->source->executeQuery($sql)->iterateAssociative() as $row) {
            try {
                $anneeUniversitaire = $yearRepository->findOneBy(['oldId' => (int) $row['annee_universitaire_id']]);
                if (null === $anneeUniversitaire) {
                    ++$skipped;
                    if (count($messages) < 20) {
                        $messages[] = sprintf(
                            'Calendrier V3 #%s ignoré: année universitaire V3 #%s non résolue.',
                            $row['id'],
                            $row['annee_universitaire_id'],
                        );
                    }
                    ++$processed;
                    $this->flushBatch($context, $processed);
                    continue;
                }

                $entity = $calendarRepository->findOneBy(['oldId' => (int) $row['id']]);
                $isNew = null === $entity;
                $entity ??= new StructureCalendrier();

                $entity
                    ->setOldId((int) $row['id'])
                    ->setAnneeUniversitaire($anneeUniversitaire)
                    ->setSemaineFormation((int) $row['semaine_formation'])
                    ->setSemaineReelle((int) $row['semaine_reelle'])
                    ->setDateLundi(new \DateTimeImmutable((string) $row['date_lundi']));

                if ($isNew) {
                    $this->entityManager->persist($entity);
                    ++$created;
                } else {
                    ++$updated;
                }
            } catch (\Throwable $e) {
                ++$failed;
                if (count($messages) < 20) {
                    $messages[] = sprintf('Calendrier V3 #%s: %s', $row['id'], $e->getMessage());
                }
            }

            ++$processed;
            $this->flushBatch($context, $processed);
        }

        $this->flushAndClear($context);

        return new MigrationResult($created, $updated, $skipped, $failed, $messages);
    }
}
