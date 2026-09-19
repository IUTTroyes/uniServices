<?php

namespace App\Migration\IntranetV3\Apc;

use App\Entity\Apc\ApcCompetence;
use App\Entity\Apc\ApcReferentiel;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\Contract\MigratorInterface;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;

final class ApcCompetenceMigrator extends AbstractMigrator
{
    public function getName(): string
    {
        return 'apc-competences';
    }

    /** @return list<class-string<MigratorInterface>> */
    public function getDependencies(): array
    {
        return [ApcReferentielMigrator::class];
    }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $created = $updated = $skipped = $failed = $processed = 0;
        $messages = [];

        $sql = <<<'SQL'
SELECT
    c.id,
    c.libelle,
    c.nom_court,
    c.couleur,
    r.libelle AS referentiel_libelle,
    r.annee_publication,
    r.departement_id,
    td.sigle AS type_diplome_sigle
FROM apc_competence c
INNER JOIN apc_referentiel r ON r.id = c.apc_referentiel_id
INNER JOIN type_diplome td ON td.id = r.type_diplome_id
ORDER BY c.id
SQL;

        foreach ($this->source->executeQuery($sql)->iterateAssociative() as $row) {
            try {
                $referentiel = $this->findReferentiel($row);
                if (null === $referentiel) {
                    ++$skipped;
                    if (count($messages) < 20) {
                        $messages[] = sprintf('Compétence APC V3 #%s ignorée: référentiel non résolu.', $row['id']);
                    }
                    ++$processed;
                    $this->flushBatch($context, $processed);
                    continue;
                }

                $entity = $this->entityManager->getRepository(ApcCompetence::class)->findOneBy([
                    'oldId' => (int) $row['id'],
                    'referentiel' => $referentiel,
                ]);
                $isNew = null === $entity;
                $entity ??= new ApcCompetence();

                $entity
                    ->setOldId((int) $row['id'])
                    ->setLibelle((string) $row['libelle'])
                    ->setNomCourt($row['nom_court'] ?: null)
                    ->setCouleur($row['couleur'] ?: null)
                    ->setReferentiel($referentiel);

                if ($isNew) {
                    $this->entityManager->persist($entity);
                    ++$created;
                } else {
                    ++$updated;
                }
            } catch (\Throwable $e) {
                ++$failed;
                if (count($messages) < 20) {
                    $messages[] = sprintf('Compétence APC V3 #%s: %s', $row['id'], $e->getMessage());
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
            ->setParameter('sigle', (string) $row['type_diplome_sigle'])
            ->setParameter('departementOldId', null !== $row['departement_id'] ? (int) $row['departement_id'] : null)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
