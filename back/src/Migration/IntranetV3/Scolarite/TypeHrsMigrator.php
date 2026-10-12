<?php

namespace App\Migration\IntranetV3\Scolarite;

use App\Entity\Personnel\PersonnelEnseignantTypeHrs;
use App\Enum\TypeHrsEnum;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;

final class TypeHrsMigrator extends AbstractMigrator
{
    public function getName(): string
    {
        return 'types-hrs';
    }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $created = $updated = $skipped = $failed = $processed = 0;
        $messages = [];

        $sql = 'SELECT id, libelle, type, inclu_service, maximum FROM type_hrs ORDER BY id';
        foreach ($this->source->executeQuery($sql)->iterateAssociative() as $row) {
            try {
                $type = !empty($row['type']) ? TypeHrsEnum::tryFrom((string) $row['type']) : null;
                $entity = $this->entityManager->getRepository(PersonnelEnseignantTypeHrs::class)->findOneBy([
                    'libelle' => (string) $row['libelle'],
                    'type' => $type,
                ]);
                $isNew = null === $entity;
                $entity ??= new PersonnelEnseignantTypeHrs();

                $entity
                    ->setLibelle((string) $row['libelle'])
                    ->setIncluService((bool) $row['inclu_service'])
                    ->setMaximum((float) $row['maximum']);
                $entity->setType($type);

                if ($isNew) {
                    $this->entityManager->persist($entity);
                    ++$created;
                } else {
                    ++$updated;
                }
            } catch (\Throwable $e) {
                ++$failed;
                if (count($messages) < 20) {
                    $messages[] = sprintf('Type HRS V3 #%s: %s', $row['id'], $e->getMessage());
                }
            }

            ++$processed;
            $this->flushBatch($context, $processed);
        }

        $this->flushAndClear($context);

        return new MigrationResult($created, $updated, $skipped, $failed, $messages);
    }
}
