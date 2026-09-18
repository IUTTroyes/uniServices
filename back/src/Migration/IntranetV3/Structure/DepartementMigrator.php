<?php

namespace App\Migration\IntranetV3\Structure;

use App\Entity\Structure\StructureDepartement;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;

final class DepartementMigrator extends AbstractMigrator
{
    public function getName(): string
    {
        return 'departements';
    }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $rows = $this->source->fetchAllAssociative(
            'SELECT id, libelle, logo_name, tel_contact, couleur, site_web, description, actif, opt_materiel, opt_edt, opt_stage, respri_id FROM departement ORDER BY id'
        );
        $repository = $this->entityManager->getRepository(StructureDepartement::class);
        $created = $updated = $failed = 0;
        $messages = [];

        foreach ($rows as $row) {
            try {
                $entity = $repository->findOneBy(['oldId' => (int) $row['id']]);
                $isNew = null === $entity;
                $entity ??= new StructureDepartement();

                $entity
                    ->setOldId((int) $row['id'])
                    ->setLibelle((string) $row['libelle'])
                    ->setLogoName($row['logo_name'] ?: null)
                    ->setTelContact($row['tel_contact'] ?: null)
                    ->setCouleur($row['couleur'] ?: null)
                    ->setSiteWeb($row['site_web'] ?: null)
                    ->setDescription($row['description'] ?: null)
                    ->setActif((bool) $row['actif'])
                    ->setOpt([
                        'materiel' => (bool) $row['opt_materiel'],
                        'edt' => (bool) $row['opt_edt'],
                        'stage' => (bool) $row['opt_stage'],
                        'resp_ri' => null !== $row['respri_id'] ? (string) $row['respri_id'] : '',
                    ]);

                if ($isNew) {
                    $this->entityManager->persist($entity);
                    ++$created;
                } else {
                    ++$updated;
                }
            } catch (\Throwable $e) {
                ++$failed;
                $messages[] = sprintf('Departement #%s: %s', $row['id'], $e->getMessage());
            }
        }

        $this->flush($context);

        return new MigrationResult($created, $updated, 0, $failed, $messages);
    }
}
