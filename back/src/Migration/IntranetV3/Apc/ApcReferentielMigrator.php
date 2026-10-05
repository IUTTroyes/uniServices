<?php

namespace App\Migration\IntranetV3\Apc;

use App\Entity\Apc\ApcReferentiel;
use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureTypeDiplome;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\Contract\MigratorInterface;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;
use App\Migration\IntranetV3\Structure\DepartementMigrator;
use App\Migration\IntranetV3\Structure\TypeDiplomeMigrator;

final class ApcReferentielMigrator extends AbstractMigrator
{
    public function getName(): string
    {
        return 'apc-referentiels';
    }

    /** @return list<class-string<MigratorInterface>> */
    public function getDependencies(): array
    {
        return [TypeDiplomeMigrator::class, DepartementMigrator::class];
    }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $created = $updated = $skipped = $failed = $processed = 0;
        $messages = [];

        $sql = <<<'SQL'
SELECT
    r.id,
    r.libelle,
    r.description,
    r.annee_publication,
    r.departement_id,
    td.sigle AS type_diplome_sigle
FROM apc_referentiel r
INNER JOIN type_diplome td ON td.id = r.type_diplome_id
ORDER BY r.id
SQL;

        foreach ($this->source->executeQuery($sql)->iterateAssociative() as $row) {
            try {
                $typeDiplome = $this->entityManager->getRepository(StructureTypeDiplome::class)
                    ->findOneBy(['sigle' => (string) $row['type_diplome_sigle']]);
                $departement = null !== $row['departement_id']
                    ? $this->entityManager->getRepository(StructureDepartement::class)
                        ->findOneBy(['oldId' => (int) $row['departement_id']])
                    : null;

                if (null === $typeDiplome) {
                    ++$skipped;
                    if (count($messages) < 20) {
                        $messages[] = sprintf('Référentiel APC V3 #%s ignoré: type diplôme %s non résolu.', $row['id'], $row['type_diplome_sigle']);
                    }
                    ++$processed;
                    $this->flushBatch($context, $processed);
                    continue;
                }

                $criteria = [
                    'libelle' => (string) $row['libelle'],
                    'anneePublication' => null !== $row['annee_publication'] ? (int) $row['annee_publication'] : null,
                    'typeDiplome' => $typeDiplome,
                    'departement' => $departement,
                ];

                $entity = $this->entityManager->getRepository(ApcReferentiel::class)->findOneBy($criteria);
                $isNew = null === $entity;
                $entity ??= new ApcReferentiel();

                $entity
                    ->setLibelle((string) $row['libelle'])
                    ->setDescription($row['description'] ?: null)
                    ->setAnneePublication(null !== $row['annee_publication'] ? (int) $row['annee_publication'] : null)
                    ->setDepartement($departement)
                    ->setTypeDiplome($typeDiplome);

                if ($isNew) {
                    $this->entityManager->persist($entity);
                    ++$created;
                } else {
                    ++$updated;
                }
            } catch (\Throwable $e) {
                ++$failed;
                if (count($messages) < 20) {
                    $messages[] = sprintf('Référentiel APC V3 #%s: %s', $row['id'], $e->getMessage());
                }
            }

            ++$processed;
            $this->flushBatch($context, $processed);
        }

        $this->flushAndClear($context);

        return new MigrationResult($created, $updated, $skipped, $failed, $messages);
    }
}
