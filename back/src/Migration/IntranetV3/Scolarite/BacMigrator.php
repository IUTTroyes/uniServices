<?php

namespace App\Migration\IntranetV3\Scolarite;

use App\Entity\Scolarite\ScolBac;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;

final class BacMigrator extends AbstractMigrator
{
    public function getName(): string
    {
        return 'bacs';
    }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $rows = $this->source->fetchAllAssociative('SELECT id, libelle, libelle_long, code_apogee, type_bac FROM bac ORDER BY id');
        $repository = $this->entityManager->getRepository(ScolBac::class);
        $created = $updated = $failed = 0;
        $messages = [];

        foreach ($rows as $row) {
            try {
                $entity = $repository->findOneBy(['oldId' => (int) $row['id']]);
                $isNew = null === $entity;
                $entity ??= new ScolBac();

                $entity
                    ->setOldId((int) $row['id'])
                    ->setLibelle((string) $row['libelle'])
                    ->setLibelleLong((string) $row['libelle_long'])
                    ->setCodeApogee($row['code_apogee'] ?: null)
                    ->setTypeBac($row['type_bac'] ?: null);

                if ($isNew) {
                    $this->entityManager->persist($entity);
                    ++$created;
                } else {
                    ++$updated;
                }
            } catch (\Throwable $e) {
                ++$failed;
                $messages[] = sprintf('Bac #%s: %s', $row['id'], $e->getMessage());
            }
        }

        $this->flush($context);

        return new MigrationResult($created, $updated, 0, $failed, $messages);
    }
}
