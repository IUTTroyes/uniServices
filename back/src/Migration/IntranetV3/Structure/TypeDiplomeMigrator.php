<?php

namespace App\Migration\IntranetV3\Structure;

use App\Entity\Structure\StructureTypeDiplome;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;

final class TypeDiplomeMigrator extends AbstractMigrator
{
    public function getName(): string
    {
        return 'type-diplomes';
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $created = $updated = $skipped = $failed = $processed = 0;
        $messages = [];

        $sql = <<<'SQL'
SELECT id, libelle, sigle, nb_semestres, niveau_entree, niveau_sortie, apc
FROM type_diplome
ORDER BY id
SQL;

        foreach ($this->source->executeQuery($sql)->iterateAssociative() as $row) {
            try {
                $entity = $this->entityManager->getRepository(StructureTypeDiplome::class)
                    ->findOneBy(['sigle' => (string) $row['sigle']]);
                $isNew = null === $entity;
                $entity ??= new StructureTypeDiplome();

                $entity
                    ->setLibelle((string) $row['libelle'])
                    ->setSigle((string) $row['sigle'])
                    ->setApc((bool) $row['apc'])
                    ->setNbSemestres((int) $row['nb_semestres'])
                    ->setNiveauEntree((int) $row['niveau_entree'])
                    ->setNiveauSortie((int) $row['niveau_sortie']);

                if ($isNew) {
                    $this->entityManager->persist($entity);
                    ++$created;
                } else {
                    ++$updated;
                }
            } catch (\Throwable $e) {
                ++$failed;
                if (count($messages) < 20) {
                    $messages[] = sprintf('Type diplôme V3 #%s: %s', $row['id'], $e->getMessage());
                }
            }

            ++$processed;
            $this->flushBatch($context, $processed);
        }

        $this->flushAndClear($context);

        return new MigrationResult($created, $updated, $skipped, $failed, $messages);
    }
}
