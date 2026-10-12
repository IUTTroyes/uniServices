<?php

namespace App\Migration\IntranetV3\Structure;

use App\Entity\Salle;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;

final class SalleMigrator extends AbstractMigrator
{
    public function getName(): string
    {
        return 'salles';
    }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $created = $updated = $failed = $processed = 0;
        $repository = $this->entityManager->getRepository(Salle::class);

        foreach ($this->source->executeQuery(
            'SELECT id, libelle, capacite, type FROM salle ORDER BY id'
        )->iterateAssociative() as $row) {
            try {
                $entity = $repository->findOneBy(['oldId' => (int) $row['id']]);
                $isNew = null === $entity;
                $entity ??= new Salle();

                $entity
                    ->setOldId((int) $row['id'])
                    ->setLibelle((string) $row['libelle'])
                    ->setCapacite(null !== $row['capacite'] ? (int) $row['capacite'] : null)
                    ->setType($row['type'] ?: null);

                if ($isNew) {
                    $this->entityManager->persist($entity);
                    ++$created;
                } else {
                    ++$updated;
                }
            } catch (\Throwable) {
                ++$failed;
            }

            ++$processed;
            $this->flushBatch($context, $processed);
        }

        $this->flushAndClear($context);

        return new MigrationResult($created, $updated, 0, $failed);
    }
}
