<?php

namespace App\Migration\IntranetV3\Apc;

use App\Entity\Apc\ApcParcours;
use App\Entity\Apc\ApcReferentiel;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructureTypeDiplome;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\Contract\MigratorInterface;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;
use App\Migration\IntranetV3\Structure\TypeDiplomeMigrator;

final class ApcDiplomeLinkMigrator extends AbstractMigrator
{
    public function getName(): string
    {
        return 'apc-diplome-liens';
    }

    /** @return list<class-string<MigratorInterface>> */
    public function getDependencies(): array
    {
        return [ApcParcoursMigrator::class, ApcReferentielMigrator::class, TypeDiplomeMigrator::class];
    }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $created = 0;
        $updated = $skipped = $failed = $processed = 0;
        $messages = [];

        $sql = <<<'SQL'
SELECT
    d.id,
    d.type_diplome_id,
    d.referentiel_id,
    d.apc_parcours_id,
    td.sigle AS type_diplome_sigle,
    r.libelle AS referentiel_libelle,
    r.annee_publication,
    r.departement_id,
    rtd.sigle AS referentiel_type_diplome_sigle
FROM diplome d
LEFT JOIN type_diplome td ON td.id = d.type_diplome_id
LEFT JOIN apc_referentiel r ON r.id = d.referentiel_id
LEFT JOIN type_diplome rtd ON rtd.id = r.type_diplome_id
ORDER BY d.id
SQL;

        foreach ($this->source->executeQuery($sql)->iterateAssociative() as $row) {
            try {
                $diplome = $this->entityManager->getRepository(StructureDiplome::class)
                    ->findOneBy(['oldId' => (int) $row['id']]);

                if (null === $diplome) {
                    ++$skipped;
                    ++$processed;
                    $this->flushBatch($context, $processed);
                    continue;
                }

                if (!empty($row['type_diplome_sigle'])) {
                    $typeDiplome = $this->entityManager->getRepository(StructureTypeDiplome::class)
                        ->findOneBy(['sigle' => (string) $row['type_diplome_sigle']]);
                    $diplome->setTypeDiplome($typeDiplome);
                }

                if (!empty($row['referentiel_id'])) {
                    $referentiel = $this->findReferentiel($row);
                    $diplome->setReferentiel($referentiel);
                }

                if (!empty($row['apc_parcours_id'])) {
                    $parcours = $this->entityManager->getRepository(ApcParcours::class)
                        ->findOneBy(['oldId' => (int) $row['apc_parcours_id']]);
                    $diplome->setParcours($parcours);
                }

                ++$updated;
            } catch (\Throwable $e) {
                ++$failed;
                if (count($messages) < 20) {
                    $messages[] = sprintf('Diplôme V3 #%s / APC: %s', $row['id'], $e->getMessage());
                }
            }

            ++$processed;
            $this->flushBatch($context, $processed);
        }

        $this->flushAndClear($context);

        return new MigrationResult($created, $updated, $skipped, $failed, $messages);
    }

    /** @param array<string,mixed> $row */
    private function findReferentiel(array $row): ?ApcReferentiel
    {
        if (empty($row['referentiel_libelle']) || empty($row['referentiel_type_diplome_sigle'])) {
            return null;
        }

        return $this->entityManager->createQueryBuilder()
            ->select('r')
            ->from(ApcReferentiel::class, 'r')
            ->innerJoin('r.typeDiplome', 'td')
            ->leftJoin('r.departement', 'dep')
            ->andWhere('r.libelle = :libelle')
            ->andWhere('r.anneePublication = :anneePublication')
            ->andWhere('td.sigle = :sigle')
            ->andWhere('(dep.oldId = :departementOldId OR (:departementOldId IS NULL AND dep.id IS NULL))')
            ->setParameter('libelle', (string) $row['referentiel_libelle'])
            ->setParameter('anneePublication', null !== $row['annee_publication'] ? (int) $row['annee_publication'] : null)
            ->setParameter('sigle', (string) $row['referentiel_type_diplome_sigle'])
            ->setParameter('departementOldId', null !== $row['departement_id'] ? (int) $row['departement_id'] : null)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
