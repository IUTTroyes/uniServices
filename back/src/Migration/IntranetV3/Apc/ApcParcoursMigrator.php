<?php

namespace App\Migration\IntranetV3\Apc;

use App\Entity\Apc\ApcCompetence;
use App\Entity\Apc\ApcNiveau;
use App\Entity\Apc\ApcParcours;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\Contract\MigratorInterface;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;

final class ApcParcoursMigrator extends AbstractMigrator
{
    public function getName(): string
    {
        return 'apc-parcours';
    }

    /** @return list<class-string<MigratorInterface>> */
    public function getDependencies(): array
    {
        return [ApcNiveauMigrator::class];
    }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $created = $updated = $skipped = $failed = $processed = 0;
        $messages = [];

        $sql = <<<'SQL'
SELECT id, libelle, code, actif, couleur, formation_continue
FROM apc_parcours
ORDER BY id
SQL;

        foreach ($this->source->executeQuery($sql)->iterateAssociative() as $row) {
            try {
                $entity = $this->entityManager->getRepository(ApcParcours::class)->findOneBy([
                    'oldId' => (int) $row['id'],
                ]);
                $isNew = null === $entity;
                $entity ??= new ApcParcours();

                $entity
                    ->setOldId((int) $row['id'])
                    ->setLibelle((string) $row['libelle'])
                    ->setSigle($row['code'] ?: null)
                    ->setActif((bool) $row['actif'])
                    ->setCouleur($row['couleur'] ?: null)
                    ->setOpt(['formation_continue' => (bool) $row['formation_continue']]);

                if ($isNew) {
                    $this->entityManager->persist($entity);
                    ++$created;
                } else {
                    ++$updated;
                }
            } catch (\Throwable $e) {
                ++$failed;
                if (count($messages) < 20) {
                    $messages[] = sprintf('Parcours APC V3 #%s: %s', $row['id'], $e->getMessage());
                }
            }

            ++$processed;
            $this->flushBatch($context, $processed);
        }

        $this->flushAndClear($context);

        $linkSql = <<<'SQL'
SELECT pn.parcours_id, pn.niveau_id, n.competence_id, n.ordre AS niveau_ordre
FROM apc_parcours_niveau pn
INNER JOIN apc_niveau n ON n.id = pn.niveau_id
ORDER BY pn.parcours_id, pn.niveau_id
SQL;

        foreach ($this->source->executeQuery($linkSql)->iterateAssociative() as $row) {
            try {
                $parcours = $this->entityManager->getRepository(ApcParcours::class)
                    ->findOneBy(['oldId' => (int) $row['parcours_id']]);
                $competence = $this->entityManager->getRepository(ApcCompetence::class)
                    ->findOneBy(['oldId' => (int) $row['competence_id']]);
                $niveau = null !== $competence
                    ? $this->entityManager->getRepository(ApcNiveau::class)->findOneBy([
                        'competence' => $competence,
                        'ordre' => (int) $row['niveau_ordre'],
                    ])
                    : null;

                if (null === $parcours || null === $niveau) {
                    ++$skipped;
                    if (count($messages) < 20) {
                        $messages[] = sprintf(
                            'Liaison parcours/niveau V3 #%s/#%s ignorée: référence non résolue.',
                            $row['parcours_id'],
                            $row['niveau_id'],
                        );
                    }
                    continue;
                }

                $niveau->addParcours($parcours);
                $this->entityManager->persist($niveau);
            } catch (\Throwable $e) {
                ++$failed;
                if (count($messages) < 20) {
                    $messages[] = sprintf(
                        'Liaison parcours/niveau V3 #%s/#%s: %s',
                        $row['parcours_id'],
                        $row['niveau_id'],
                        $e->getMessage(),
                    );
                }
            }
        }

        $this->flushAndClear($context);

        return new MigrationResult($created, $updated, $skipped, $failed, $messages);
    }
}
