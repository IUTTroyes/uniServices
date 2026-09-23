<?php

namespace StageBundle\Migration\IntranetV3;

use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;
use StageBundle\Entity\Stages\Contact;

final class ContactMigrator extends AbstractMigrator
{
    public function getName(): string { return 'stage-contacts'; }
    public function getDependencies(): array { return []; }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $created = $updated = $skipped = $failed = $processed = 0;
        $messages = [];
        $idsSql = <<<'SQL'
SELECT DISTINCT contact_id FROM (
    SELECT se.tuteur_id AS contact_id FROM stage_etudiant se WHERE se.tuteur_id IS NOT NULL
    UNION
    SELECT e.responsable_id AS contact_id
    FROM entreprise e
    INNER JOIN stage_etudiant se ON se.entreprise_id = e.id
    WHERE e.responsable_id IS NOT NULL
) contacts
SQL;
        $total = (int) $this->source->fetchOne('SELECT COUNT(*) FROM ('.$idsSql.') c');
        $this->startProgress($context, 'Contacts de stage référencés', $total);
        $sql = 'SELECT c.id, c.nom, c.prenom, c.fonction, c.telephone, c.email, c.portable, c.civilite, c.fax FROM contact c INNER JOIN ('.$idsSql.') refs ON refs.contact_id = c.id ORDER BY c.id';

        foreach ($this->source->executeQuery($sql)->iterateAssociative() as $row) {
            try {
                $entity = $this->entityManager->getRepository(Contact::class)->findOneBy(['oldId' => (int) $row['id']]);
                $isNew = null === $entity;
                $entity ??= new Contact();
                $entity->setOldId((int) $row['id'])
                    ->setNom($row['nom'])->setPrenom($row['prenom'])->setFonction($row['fonction'])
                    ->setTelephone($row['telephone'])->setEmail($row['email'])->setPortable($row['portable'])
                    ->setCivilite($row['civilite'])->setFax($row['fax']);
                if ($isNew) { $this->entityManager->persist($entity); ++$created; } else { ++$updated; }
            } catch (\Throwable $e) {
                ++$failed;
                if (count($messages) < 20) $messages[] = sprintf('Contact #%s: %s', $row['id'], $e->getMessage());
            }
            ++$processed; $context->advanceProgress();
            if (0 === $processed % self::BATCH_SIZE) $this->flushAndClear($context);
        }
        $this->flushAndClear($context); $this->finishProgress($context);
        return new MigrationResult($created, $updated, $skipped, $failed, $messages);
    }
}
