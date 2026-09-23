<?php

namespace App\Migration\IntranetV3\Structure;

use App\Entity\Apc\ApcReferentiel;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructurePn;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\Apc\ApcDiplomeLinkMigrator;
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
        return [DiplomeMigrator::class, AnneeUniversitaireMigrator::class, ApcDiplomeLinkMigrator::class];
    }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $repository = $this->entityManager->getRepository(StructurePn::class);
        $diplomeRepository = $this->entityManager->getRepository(StructureDiplome::class);
        $anneeUniversitaireRepository = $this->entityManager->getRepository(StructureAnneeUniversitaire::class);
        $created = $updated = $skipped = $failed = 0;
        $messages = [];
        $apcLinked = 0;
        $apcMissing = 0;

        // V4 starts its trustworthy structural history with 2026-2027.
        // V3 did not version the maquette, therefore cloning today's structure
        // into older academic years would manufacture a false history.
        $anneeUniversitaire = $anneeUniversitaireRepository->findOneBy(['annee' => 2026]);
        if (null === $anneeUniversitaire) {
            return new MigrationResult(0, 0, 0, 1, [
                'PN 2026-2027 non migré: année universitaire 2026 introuvable.',
            ]);
        }

        $diplomeOldIds = $this->source->fetchFirstColumn(
            'SELECT DISTINCT diplome_id FROM annee WHERE diplome_id IS NOT NULL ORDER BY diplome_id'
        );

        foreach ($diplomeOldIds as $diplomeOldId) {
            try {
                $diplome = $diplomeRepository->findOneBy(['oldId' => (int) $diplomeOldId]);
                if (null === $diplome) {
                    ++$skipped;
                    $messages[] = sprintf('PN 2026-2027 ignoré: diplôme V3 #%s introuvable.', $diplomeOldId);
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
                    ->setAnneePublication(2026)
                    ->setLibelle(sprintf('%s — %s', $diplome->getLibelle(), $anneeUniversitaire->getLibelle()));

                // Le référentiel APC est stable et porté par le diplôme. Le PN annuel
                // doit néanmoins mémoriser explicitement le référentiel utilisé.
                $referentiel = $diplome->getReferentiel();
                if ($referentiel instanceof ApcReferentiel) {
                    $entity->setApcReferentiel($referentiel);
                    ++$apcLinked;
                } elseif ($diplome->getTypeDiplome()?->isApc()) {
                    ++$apcMissing;
                    $messages[] = sprintf(
                        'PN 2026-2027 diplôme V3 #%s: diplôme APC sans référentiel associé.',
                        $diplomeOldId,
                    );
                } else {
                    $entity->setApcReferentiel(null);
                }

                if ($isNew) {
                    $this->entityManager->persist($entity);
                    ++$created;
                } else {
                    ++$updated;
                }
            } catch (\Throwable $e) {
                ++$failed;
                $messages[] = sprintf('PN 2026-2027 diplôme #%s: %s', $diplomeOldId, $e->getMessage());
            }
        }

        $this->flush($context);

        $messages[] = sprintf(
            'Référentiels APC des PN: associés=%d, diplômes APC sans référentiel=%d.',
            $apcLinked,
            $apcMissing,
        );

        return new MigrationResult($created, $updated, $skipped, $failed, $messages);
    }
}
