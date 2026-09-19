<?php

namespace App\Migration\IntranetV3\Structure;

use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructurePn;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\Contract\MigratorInterface;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;

final class PnMigrator extends AbstractMigrator
{
    public function getName(): string
    {
        return 'pns';
    }

    /** @return list<class-string<MigratorInterface>> */
    public function getDependencies(): array
    {
        return [DiplomeMigrator::class, AnneeUniversitaireMigrator::class];
    }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $repository = $this->entityManager->getRepository(StructurePn::class);
        $diplomeRepository = $this->entityManager->getRepository(StructureDiplome::class);
        $anneeUniversitaireRepository = $this->entityManager->getRepository(StructureAnneeUniversitaire::class);
        $created = $updated = $skipped = $failed = 0;
        $messages = [];

        // V3 ne versionnait pas réellement la structure. On reconstruit donc une
        // racine de snapshot par couple (diplôme, année universitaire) effectivement
        // utilisé dans les scolarités. Les descendants seront clonés depuis l'état
        // de structure V3 connu aujourd'hui.
        $sql = <<<'SQL'
SELECT DISTINCT a.diplome_id, sc.annee_universitaire_id
FROM scolarite sc
INNER JOIN semestre s ON s.id = sc.semestre_id
INNER JOIN annee a ON a.id = s.annee_id
WHERE sc.annee_universitaire_id IS NOT NULL
ORDER BY sc.annee_universitaire_id, a.diplome_id
SQL;

        foreach ($this->source->iterateAssociative($sql) as $row) {
            try {
                $diplome = $diplomeRepository->findOneBy(['oldId' => (int) $row['diplome_id']]);
                $anneeUniversitaire = $anneeUniversitaireRepository->findOneBy(['oldId' => (int) $row['annee_universitaire_id']]);

                if (null === $diplome || null === $anneeUniversitaire) {
                    ++$skipped;
                    $messages[] = sprintf(
                        'PN snapshot skipped: diplôme V3 #%s ou année universitaire V3 #%s introuvable.',
                        $row['diplome_id'],
                        $row['annee_universitaire_id'],
                    );
                    continue;
                }

                $entity = $repository->findOneBy([
                    'diplome' => $diplome,
                    'anneeUniversitaire' => $anneeUniversitaire,
                ]);
                $isNew = null === $entity;
                $entity ??= new StructurePn($diplome);

                $entity
                    ->setDiplome($diplome)
                    ->setAnneeUniversitaire($anneeUniversitaire)
                    ->setAnneePublication((int) $anneeUniversitaire->getAnnee())
                    ->setLibelle(sprintf('%s — %s', $diplome->getLibelle(), $anneeUniversitaire->getLibelle()));

                if ($isNew) {
                    $this->entityManager->persist($entity);
                    ++$created;
                } else {
                    ++$updated;
                }
            } catch (\Throwable $e) {
                ++$failed;
                $messages[] = sprintf(
                    'PN snapshot diplôme #%s / année #%s: %s',
                    $row['diplome_id'],
                    $row['annee_universitaire_id'],
                    $e->getMessage(),
                );
            }
        }

        $this->flush($context);

        return new MigrationResult($created, $updated, $skipped, $failed, $messages);
    }
}
