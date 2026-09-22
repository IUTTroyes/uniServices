<?php

namespace App\Migration\IntranetV3\Structure;

use App\Entity\Structure\StructureAnnee;
use App\Entity\Structure\StructurePn;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\Contract\MigratorInterface;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;

final class AnneeMigrator extends AbstractMigrator
{
    public function getName(): string
    {
        return 'annees';
    }

    /** @return list<class-string<MigratorInterface>> */
    public function getDependencies(): array
    {
        return [PnMigrator::class];
    }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $repository = $this->entityManager->getRepository(StructureAnnee::class);
        $pnRepository = $this->entityManager->getRepository(StructurePn::class);
        $created = $updated = $skipped = $failed = $processed = 0;
        $messages = [];

        $pnIds = $this->entityManager->createQueryBuilder()
            ->select('pn.id')
            ->from(StructurePn::class, 'pn')
            ->orderBy('pn.id', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();

        foreach ($pnIds as $pnId) {
            $pn = $pnRepository->find((int) $pnId);
            if (null === $pn) {
                continue;
            }
            $diplomeOldId = $pn->getDiplome()?->getOldId();
            if (null === $diplomeOldId || null === $pn->getAnneeUniversitaire()) {
                ++$skipped;
                continue;
            }

            $sql = <<<'SQL'
SELECT id, diplome_id, libelle, ordre, libelle_long, actif, couleur, code_version, code_etape, opt_alternance
FROM annee
WHERE diplome_id = :diplome_id
ORDER BY ordre, id
SQL;

            foreach ($this->source->iterateAssociative($sql, ['diplome_id' => $diplomeOldId]) as $row) {
                try {
                    $entity = $repository->findOneBy([
                        'oldId' => (int) $row['id'],
                        'pn' => $pn,
                    ]);
                    $isNew = null === $entity;
                    $entity ??= new StructureAnnee();

                    $entity
                        ->setOldId((int) $row['id'])
                        ->setPn($pn)
                        ->setLibelle((string) $row['libelle'])
                        ->setOrdre((int) $row['ordre'])
                        ->setLibelleLong($row['libelle_long'] ?: null)
                        ->setActif((bool) $row['actif'])
                        ->setCouleur($row['couleur'] ?: null)
                        ->setApogeeCodeVersion($row['code_version'] ?: null)
                        ->setApogeeCodeEtape($row['code_etape'] ?: null)
                        ->setOpt(['alternance' => (bool) $row['opt_alternance']]);

                    if ($isNew) {
                        $this->entityManager->persist($entity);
                        ++$created;
                    } else {
                        ++$updated;
                    }
                } catch (\Throwable $e) {
                    ++$failed;
                    $messages[] = sprintf(
                        'Annee V3 #%s / PN %s: %s',
                        $row['id'],
                        $pn->getId() ?? 'new',
                        $e->getMessage(),
                    );
                }

                ++$processed;
                $this->flushAndClearBatch($context, $processed);

                if (!$this->entityManager->contains($pn)) {
                    $pn = $pnRepository->find((int) $pnId);
                    if (null === $pn) {
                        break;
                    }
                }
            }

            $this->entityManager->clear();
        }

        $this->flush($context);

        return new MigrationResult($created, $updated, $skipped, $failed, $messages);
    }
}
