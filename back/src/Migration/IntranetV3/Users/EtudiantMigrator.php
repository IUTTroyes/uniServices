<?php

namespace App\Migration\IntranetV3\Users;

use App\Entity\Scolarite\ScolBac;
use App\Entity\Users\Etudiant;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;
use App\Migration\IntranetV3\Scolarite\BacMigrator;

final class EtudiantMigrator extends AbstractMigrator
{
    public function getName(): string
    {
        return 'etudiants';
    }

    public function getDependencies(): array
    {
        return [BacMigrator::class];
    }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $created = $updated = $failed = $processed = 0;
        $messages = [];

        $sql = 'SELECT id, username, mail_univ, mail_perso, prenom, nom, photo_name, num_etudiant, num_ine, annee_bac, boursier, amenagements_particuliers, promotion, annee_sortie, bac_id FROM etudiant ORDER BY id';
        $rows = $this->source->executeQuery($sql)->iterateAssociative();

        foreach ($rows as $row) {
            try {
                $repository = $this->entityManager->getRepository(Etudiant::class);
                $bacRepository = $this->entityManager->getRepository(ScolBac::class);

                $entity = $repository->findOneBy(['oldId' => (int) $row['id']]);
                $isNew = null === $entity;
                $entity ??= new Etudiant();

                $entity
                    ->setOldId((int) $row['id'])
                    ->setUsername((string) $row['username'])
                    ->setMailUniv((string) $row['mail_univ'])
                    ->setPrenom((string) $row['prenom'])
                    ->setNom((string) $row['nom'])
                    ->setPhotoName($row['photo_name'])
                    ->setNumEtudiant($row['num_etudiant'])
                    ->setNumIne($row['num_ine'])
                    ->setAnneeBac(null !== $row['annee_bac'] ? (int) $row['annee_bac'] : null)
                    ->setBoursier((bool) $row['boursier'])
                    ->setAmenagementsParticuliers($row['amenagements_particuliers'])
                    ->setPromotion(null !== $row['promotion'] ? (int) $row['promotion'] : null)
                    ->setAnneeSortie(null !== $row['annee_sortie'] ? (int) $row['annee_sortie'] : 0)
                    ->setRoles(['ROLE_ETUDIANT'])
                    ->setMailPerso($row['mail_perso']);

                if (null !== $row['bac_id']) {
                    $entity->setBac($bacRepository->findOneBy(['oldId' => (int) $row['bac_id']]));
                }

                if ($isNew) {
                    $this->entityManager->persist($entity);
                    ++$created;
                } else {
                    ++$updated;
                }
            } catch (\Throwable $e) {
                ++$failed;
                $messages[] = sprintf('Etudiant #%s: %s', $row['id'], $e->getMessage());
            }

            ++$processed;
            $this->flushBatch($context, $processed);
        }

        $this->flushAndClear($context);

        return new MigrationResult($created, $updated, 0, $failed, $messages);
    }
}
