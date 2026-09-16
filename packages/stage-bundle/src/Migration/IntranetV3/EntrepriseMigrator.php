<?php

namespace StageBundle\Migration\IntranetV3;

use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;
use App\ValueObject\Adresse;
use StageBundle\Entity\Stages\Contact;
use StageBundle\Entity\Stages\Entreprise;

final class EntrepriseMigrator extends AbstractMigrator
{
    public function getName(): string { return 'stage-entreprises'; }
    public function getDependencies(): array { return [ContactMigrator::class]; }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $created = $updated = $skipped = $failed = $processed = 0;
        $messages = []; $missingResponsables = 0;
        $total = (int) $this->source->fetchOne('SELECT COUNT(DISTINCT entreprise_id) FROM stage_etudiant WHERE entreprise_id IS NOT NULL');
        $this->startProgress($context, 'Entreprises de stage référencées', $total);
        $sql = <<<'SQL'
SELECT e.id, e.siret, e.raison_sociale, e.responsable_id,
       a.adresse1, a.adresse2, a.adresse3, a.code_postal, a.ville, a.pays
FROM entreprise e
LEFT JOIN adresse a ON a.id = e.adresse_id
INNER JOIN (SELECT DISTINCT entreprise_id FROM stage_etudiant WHERE entreprise_id IS NOT NULL) refs ON refs.entreprise_id = e.id
ORDER BY e.id
SQL;
        foreach ($this->source->executeQuery($sql)->iterateAssociative() as $row) {
            try {
                $entity = $this->entityManager->getRepository(Entreprise::class)->findOneBy(['oldId' => (int) $row['id']]);
                $isNew = null === $entity;
                $entity ??= new Entreprise();
                $responsable = null;
                if (null !== $row['responsable_id']) {
                    $responsable = $this->entityManager->getRepository(Contact::class)->findOneBy(['oldId' => (int) $row['responsable_id']]);
                    if (null === $responsable) ++$missingResponsables;
                }
                $entity->setOldId((int) $row['id'])
                    ->setSiret($row['siret'])
                    ->setRaisonSociale((string) $row['raison_sociale'])
                    ->setAdresse(self::adresse($row))
                    ->setResponsable($responsable);
                if ($isNew) { $this->entityManager->persist($entity); ++$created; } else { ++$updated; }
            } catch (\Throwable $e) {
                ++$failed;
                if (count($messages) < 20) $messages[] = sprintf('Entreprise #%s: %s', $row['id'], $e->getMessage());
            }
            ++$processed; $context->advanceProgress();
            if (0 === $processed % self::BATCH_SIZE) $this->flushAndClear($context);
        }
        $this->flushAndClear($context); $this->finishProgress($context);
        if ($missingResponsables > 0) $messages[] = sprintf('%d responsables entreprise référencés mais introuvables.', $missingResponsables);
        return new MigrationResult($created, $updated, $skipped, $failed, $messages);
    }

    /** @param array<string, mixed> $row */
    private static function adresse(array $row): ?Adresse
    {
        if (null === ($row['adresse1'] ?? null) && null === ($row['ville'] ?? null) && null === ($row['code_postal'] ?? null)) return null;
        return Adresse::fromArray(['adresse1' => $row['adresse1'] ?? null, 'adresse2' => $row['adresse2'] ?? null, 'adresse3' => $row['adresse3'] ?? null, 'code_postal' => $row['code_postal'] ?? null, 'ville' => $row['ville'] ?? null, 'pays' => $row['pays'] ?? null]);
    }
}
