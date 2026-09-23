<?php

namespace App\Migration\IntranetV3\Structure;

use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;

final class AnneeUniversitaireMigrator extends AbstractMigrator
{
    public function getName(): string
    {
        return 'annees-universitaires';
    }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $created = $updated = $failed = 0;
        $messages = [];
        $repository = $this->entityManager->getRepository(StructureAnneeUniversitaire::class);

        foreach ($this->source->fetchAllAssociative('SELECT id, libelle, annee, commentaire, active FROM annee_universitaire ORDER BY annee') as $row) {
            try {
                $entity = $repository->findOneBy(['oldId' => (int) $row['id']]);
                $isNew = null === $entity;
                $entity ??= new StructureAnneeUniversitaire();
                $entity
                    ->setOldId((int) $row['id'])
                    ->setLibelle((string) $row['libelle'])
                    ->setAnnee((int) $row['annee'])
                    ->setCommentaire($row['commentaire'])
                    ->setActif((bool) $row['active']);

                if ($isNew) {
                    $this->entityManager->persist($entity);
                    ++$created;
                } else {
                    ++$updated;
                }
            } catch (\Throwable $e) {
                ++$failed;
                $messages[] = sprintf('AnneeUniversitaire #%s: %s', $row['id'], $e->getMessage());
            }
        }

        $this->flush($context);

        return new MigrationResult($created, $updated, 0, $failed, $messages);
    }
}
