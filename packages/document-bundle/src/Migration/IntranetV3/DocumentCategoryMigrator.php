<?php

namespace DocumentBundle\Migration\IntranetV3;

use App\Entity\Structure\StructureDepartement;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;
use DocumentBundle\Entity\DocumentCategory;

final class DocumentCategoryMigrator extends AbstractMigrator
{
    public function getName(): string { return 'document-categories'; }
    public function getDependencies(): array { return []; }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $created = $updated = $skipped = $failed = $processed = $missingDepartments = $originaux = 0;
        $messages = [];
        $total = (int) $this->source->fetchOne('SELECT COUNT(*) FROM type_document');
        $this->startProgress($context, 'Catégories de documents', $total);

        $sql = 'SELECT id, libelle, departement_id, parent_id, originaux FROM type_document ORDER BY id';
        foreach ($this->source->executeQuery($sql)->iterateAssociative() as $row) {
            try {
                $entity = $this->entityManager->getRepository(DocumentCategory::class)->findOneBy(['oldId' => (int) $row['id']]);
                $isNew = null === $entity;
                $entity ??= new DocumentCategory();
                $entity->setOldId((int) $row['id'])->setLibelle((string) $row['libelle']);

                if (null !== $row['departement_id']) {
                    $departement = $this->entityManager->getRepository(StructureDepartement::class)->findOneBy(['oldId' => (int) $row['departement_id']]);
                    if (null === $departement) ++$missingDepartments;
                    $entity->setDepartement($departement);
                } else {
                    $entity->setDepartement(null);
                }
                if ((bool) $row['originaux']) ++$originaux;

                if ($isNew) { $this->entityManager->persist($entity); ++$created; } else { ++$updated; }
            } catch (\Throwable $e) {
                ++$failed;
                if (count($messages) < 20) $messages[] = sprintf('TypeDocument #%s: %s', $row['id'], $e->getMessage());
            }
            ++$processed; $context->advanceProgress();
            if (0 === $processed % self::BATCH_SIZE) $this->flushAndClear($context);
        }
        $this->flushAndClear($context);

        // Deuxième passe : l'arborescence ne peut être résolue qu'une fois toutes les catégories créées.
        foreach ($this->source->fetchAllAssociative('SELECT id, parent_id FROM type_document WHERE parent_id IS NOT NULL') as $row) {
            $entity = $this->entityManager->getRepository(DocumentCategory::class)->findOneBy(['oldId' => (int) $row['id']]);
            $parent = $this->entityManager->getRepository(DocumentCategory::class)->findOneBy(['oldId' => (int) $row['parent_id']]);
            if (null !== $entity && null !== $parent) $entity->setParent($parent);
            elseif (count($messages) < 20) $messages[] = sprintf('TypeDocument #%s: parent V3 #%s introuvable.', $row['id'], $row['parent_id']);
        }
        $this->flushAndClear($context); $this->finishProgress($context);

        if ($missingDepartments > 0) $messages[] = sprintf('%d catégorie(s) avec un département V3 introuvable ont été importées sans département.', $missingDepartments);
        if ($originaux > 0) $messages[] = sprintf('%d catégorie(s) V3 marquée(s) originaux : information non transposée, aucun équivalent métier direct dans DocumentCategory.', $originaux);

        return new MigrationResult($created, $updated, $skipped, $failed, $messages);
    }
}
