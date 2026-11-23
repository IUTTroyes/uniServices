<?php

declare(strict_types=1);

namespace App\Migration\IntranetV3\Structure;

use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;
use App\Service\Calendrier\CalendrierUniversitaireService;

/** Reprend le début annuel depuis V3 ; aucune semaine n’est persistée. */
final class CalendrierMigrator extends AbstractMigrator
{
    public function getName(): string { return 'calendriers'; }
    public function getDependencies(): array { return [AnneeUniversitaireMigrator::class]; }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $updated = $skipped = $failed = 0;
        $messages = [];
        $groupes = [];
        $ignoredWeekZero = 0;
        foreach ($this->source->fetchAllAssociative('SELECT semaine_formation, semaine_reelle, date_lundi, annee_universitaire_id FROM calendrier ORDER BY annee_universitaire_id, semaine_formation') as $row) {
            // DBAL returns V3 DATETIME(6) as a timestamp. The calendar service
            // intentionally validates civil dates only.
            $row['date_lundi'] = substr((string) $row['date_lundi'], 0, 10);
            if ((int) $row['semaine_formation'] === 0) {
                ++$ignoredWeekZero;
                continue; // week 0 precedes the first teaching week
            }
            $groupes[$row['annee_universitaire_id']][] = $row;
        }
        $service = new CalendrierUniversitaireService();
        foreach ($groupes as $oldId => $lignes) {
            try {
                $annee = $this->entityManager->getRepository(StructureAnneeUniversitaire::class)->findOneBy(['oldId' => (int) $oldId]);
                if ($annee === null) { ++$skipped; continue; }
                $debut = $service->getDateDebutDepuisCorrespondances($lignes);
                if (null === $debut) {
                    throw new \DomainException('Aucune semaine de formation positive exploitable.');
                }
                if ($annee->getDateDebutSemaineFormation() !== null && $annee->getDateDebutSemaineFormation()->format('Y-m-d') !== $debut?->format('Y-m-d')) {
                    throw new \DomainException('La date déjà configurée diffère de celle du calendrier V3.');
                }
                foreach ($lignes as $ligne) {
                    if ($ligne['date_lundi'] > $service->getDateFin($annee)->format('Y-m-d')) {
                        throw new \DomainException('Une semaine V3 dépasse le 31 août N+1.');
                    }
                }
                $annee->setDateDebutSemaineFormation($debut);
                ++$updated;
            } catch (\Throwable $exception) {
                ++$failed;
                $messages[] = sprintf('Calendrier V3 année #%s : %s', $oldId, $exception->getMessage());
            }
        }
        $this->flush($context);
        if ($ignoredWeekZero > 0) {
            $messages[] = sprintf('Semaines 0 V3 ignorées pour calculer le premier lundi de formation : %d.', $ignoredWeekZero);
        }
        return new MigrationResult(0, $updated, $skipped, $failed, $messages);
    }
}
