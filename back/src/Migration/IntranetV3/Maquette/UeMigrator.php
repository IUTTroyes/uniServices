<?php

namespace App\Migration\IntranetV3\Maquette;

use App\Entity\Structure\StructureSemestre;
use App\Entity\Structure\StructureUe;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\Contract\MigratorInterface;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;
use App\Migration\IntranetV3\Structure\SemestreMigrator;

final class UeMigrator extends AbstractMigrator
{
    public function getName(): string
    {
        return 'ues';
    }

    /** @return list<class-string<MigratorInterface>> */
    public function getDependencies(): array
    {
        return [SemestreMigrator::class];
    }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $created = $updated = $skipped = $failed = 0;
        $messages = [];

        foreach ($this->entityManager->getRepository(StructureSemestre::class)->findAll() as $semestre) {
            $semestreOldId = $semestre->getOldId();
            if (null === $semestreOldId) {
                ++$skipped;
                continue;
            }

            $sql = <<<'SQL'
SELECT id, semestre_id, libelle, numero_ue, coefficient, nb_ects, actif, bonification, code_element, apc_competence_id
FROM ue
WHERE semestre_id = :semestre_id
ORDER BY numero_ue, id
SQL;

            foreach ($this->source->iterateAssociative($sql, ['semestre_id' => $semestreOldId]) as $row) {
                try {
                    $entity = $this->entityManager->getRepository(StructureUe::class)->findOneBy([
                        'oldId' => (int) $row['id'],
                        'semestre' => $semestre,
                    ]);
                    $isNew = null === $entity;
                    $entity ??= new StructureUe();

                    $entity
                        ->setOldId((int) $row['id'])
                        ->setSemestre($semestre)
                        ->setLibelle((string) $row['libelle'])
                        ->setNumero((int) $row['numero_ue'])
                        ->setNbEcts((float) $row['nb_ects'])
                        ->setCoefficient((float) $row['coefficient'])
                        ->setActif((bool) $row['actif'])
                        ->setBonification((bool) $row['bonification'])
                        ->setCodeElement((string) ($row['code_element'] ?? ''));

                    // La compétence APC sera rattachée dans une phase dédiée une fois le référentiel APC migré.
                    if ($isNew) {
                        $this->entityManager->persist($entity);
                        ++$created;
                    } else {
                        ++$updated;
                    }
                } catch (\Throwable $e) {
                    ++$failed;
                    if (count($messages) < 20) {
                        $messages[] = sprintf('UE V3 #%s / semestre V3 #%s: %s', $row['id'], $semestreOldId, $e->getMessage());
                    }
                }
            }

            $this->flush($context);
        }

        return new MigrationResult($created, $updated, $skipped, $failed, $messages);
    }
}
