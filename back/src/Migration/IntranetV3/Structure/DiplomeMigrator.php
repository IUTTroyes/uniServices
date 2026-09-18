<?php

namespace App\Migration\IntranetV3\Structure;

use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructureTypeDiplome;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\Contract\MigratorInterface;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;

final class DiplomeMigrator extends AbstractMigrator
{
    public function getName(): string
    {
        return 'diplomes';
    }

    /** @return list<class-string<MigratorInterface>> */
    public function getDependencies(): array
    {
        return [DepartementMigrator::class, TypeDiplomeMigrator::class];
    }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $rows = $this->source->fetchAllAssociative(
            'SELECT d.id, d.departement_id, d.parent_id, d.type_diplome_id, td.sigle AS type_diplome_sigle, d.libelle, d.volume_horaire, d.code_celcat_departement, d.sigle, d.actif, d.logo_partenaire, d.key_edu_sign, d.opt_nb_jours_saisie, d.opt_suppr_absence, d.opt_anonymat, d.opt_commentaires_releve, d.opt_espace_perso_visible, d.opt_semaines_visibles, d.opt_certifie_qualite, d.opt_responsable_qualite, d.opt_update_celcat, d.saisie_cm_autorise FROM diplome d LEFT JOIN type_diplome td ON td.id = d.type_diplome_id ORDER BY d.id'
        );
        $repository = $this->entityManager->getRepository(StructureDiplome::class);
        $departementRepository = $this->entityManager->getRepository(StructureDepartement::class);
        $typeDiplomeRepository = $this->entityManager->getRepository(StructureTypeDiplome::class);
        $created = $updated = $skipped = $failed = 0;
        $messages = [];
        $missingTypeDiplomes = 0;
        $inactiveDiplomes = 0;

        foreach ($rows as $row) {
            try {
                $departement = $departementRepository->findOneBy(['oldId' => (int) $row['departement_id']]);
                if (!$departement) {
                    ++$skipped;
                    $messages[] = sprintf('Diplome #%s skipped: departement V3 #%s introuvable.', $row['id'], $row['departement_id']);
                    continue;
                }

                $typeDiplome = null;
                if (null !== $row['type_diplome_id']) {
                    $typeDiplome = $typeDiplomeRepository->findOneBy(['sigle' => (string) $row['type_diplome_sigle']]);
                    if (null === $typeDiplome) {
                        ++$missingTypeDiplomes;
                    }
                }

                if (!(bool) $row['actif']) {
                    ++$inactiveDiplomes;
                }

                $entity = $repository->findOneBy(['oldId' => (int) $row['id']]);
                $isNew = null === $entity;
                $entity ??= new StructureDiplome();

                $entity
                    ->setOldId((int) $row['id'])
                    ->setDepartement($departement)
                    ->setLibelle((string) $row['libelle'])
                    ->setVolumeHoraire((int) $row['volume_horaire'])
                    ->setCodeCelcatDepartement(null !== $row['code_celcat_departement'] ? (int) $row['code_celcat_departement'] : null)
                    ->setSigle($row['sigle'] ?: null)
                    ->setLogoPartenaire($row['logo_partenaire'] ?: null)
                    ->setKeyEduSign($row['key_edu_sign'] ?: null)
                    ->setTypeDiplome($typeDiplome)
                    ->setOpt([
                        'nb_jours_saisie_absence' => (int) $row['opt_nb_jours_saisie'],
                        'supp_absence' => (bool) $row['opt_suppr_absence'],
                        'anonymat' => (bool) $row['opt_anonymat'],
                        'commentaire_releve' => (bool) $row['opt_commentaires_releve'],
                        'espace_perso_visible' => (bool) $row['opt_espace_perso_visible'],
                        'semaine_visible' => (int) $row['opt_semaines_visibles'],
                        'certif_qualite' => (bool) $row['opt_certifie_qualite'],
                        'resp_qualite' => (int) $row['opt_responsable_qualite'],
                        'update_celcat' => (bool) $row['opt_update_celcat'],
                        'saisie_cm_autorisee' => (bool) $row['saisie_cm_autorise'],
                    ]);

                if ($isNew) {
                    $this->entityManager->persist($entity);
                    ++$created;
                } else {
                    ++$updated;
                }
            } catch (\Throwable $e) {
                ++$failed;
                $messages[] = sprintf('Diplome #%s: %s', $row['id'], $e->getMessage());
            }
        }

        // Les relations parent/enfant sont faites en second passage afin de ne pas dépendre de l'ordre des ids.
        foreach ($rows as $row) {
            if (null === $row['parent_id']) {
                continue;
            }
            $entity = $repository->findOneBy(['oldId' => (int) $row['id']]);
            $parent = $repository->findOneBy(['oldId' => (int) $row['parent_id']]);
            if ($entity && $parent) {
                $entity->setParent($parent);
            }
        }

        $this->flush($context);

        if ($missingTypeDiplomes > 0) {
            $messages[] = sprintf('Diplômes avec type V3 non résolu en V4: %d.', $missingTypeDiplomes);
        }
        if ($inactiveDiplomes > 0) {
            $messages[] = sprintf(
                'Diplômes V3 actif=false: %d. StructureDiplome ne possède pas de champ actif; ils sont conservés pour les snapshots et données historiques.',
                $inactiveDiplomes,
            );
        }

        return new MigrationResult($created, $updated, $skipped, $failed, $messages);
    }
}
