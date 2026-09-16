<?php

namespace StageBundle\Migration\IntranetV3;

use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Users\Personnel;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;
use App\Migration\IntranetV3\Structure\AnneeUniversitaireMigrator;
use App\Migration\IntranetV3\Structure\SemestreMigrator;
use App\Migration\IntranetV3\Users\PersonnelMigrator;
use StageBundle\Entity\Stages\StagePeriode;

final class StagePeriodeMigrator extends AbstractMigrator
{
    public function getName(): string
    {
        return 'stage-periodes';
    }

    public function getDependencies(): array
    {
        return [
            AnneeUniversitaireMigrator::class,
            SemestreMigrator::class,
            PersonnelMigrator::class,
        ];
    }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $created = $updated = $skipped = $failed = $processed = 0;
        $messages = [];
        $total = (int) $this->source->fetchOne('SELECT COUNT(*) FROM stage_periode');
        $this->startProgress($context, 'Périodes de stage', $total);

        $sql = <<<'SQL'
SELECT sp.id, sp.libelle, sp.nb_semaines, sp.nb_jours, sp.date_debut, sp.date_fin,
       sp.competences_visees, sp.modalite_evaluation, sp.modalite_evaluation_pedagogique,
       sp.modalite_encadrement, sp.document_rendre, sp.semestre_id, sp.dates_flexibles,
       sp.texte_libre, sp.annee_universitaire_id
FROM stage_periode sp
ORDER BY sp.id
SQL;

        foreach ($this->source->executeQuery($sql)->iterateAssociative() as $row) {
            try {
                $annee = $this->entityManager->getRepository(StructureAnneeUniversitaire::class)
                    ->findOneBy(['oldId' => (int) $row['annee_universitaire_id']]);
                if (null === $annee) {
                    ++$skipped;
                    $messages[] = sprintf('StagePeriode #%s: année universitaire V3 #%s introuvable.', $row['id'], $row['annee_universitaire_id']);
                    ++$processed;
                    $context->advanceProgress();
                    continue;
                }

                $semestre = null;
                if (null !== $row['semestre_id']) {
                    $matches = $this->entityManager->getRepository(StructureSemestre::class)->findBy([
                        'oldId' => (int) $row['semestre_id'],
                    ]);
                    $matches = array_values(array_filter(
                        $matches,
                        static fn (StructureSemestre $candidate): bool => $candidate->getAnnee()?->getPn()?->getAnneeUniversitaire()?->getId() === $annee->getId(),
                    ));
                    if (1 === count($matches)) {
                        $semestre = $matches[0];
                    }
                }

                $entity = $this->entityManager->getRepository(StagePeriode::class)->findOneBy(['oldId' => (int) $row['id']]);
                $isNew = null === $entity;
                $entity ??= new StagePeriode();

                $entity
                    ->setOldId((int) $row['id'])
                    ->setLibelle((string) $row['libelle'])
                    ->setAnneeUniversitaire($annee)
                    ->setSemestreProgramme($semestre)
                    ->setNbSemaines((int) $row['nb_semaines'])
                    ->setNbJours((int) $row['nb_jours'])
                    ->setDateDebut(new \DateTime((string) $row['date_debut']))
                    ->setDateFin(new \DateTime((string) $row['date_fin']))
                    ->setDatesFlexibles((bool) $row['dates_flexibles'])
                    ->setCompetencesVisees($row['competences_visees'])
                    ->setModalitesEvaluationEntreprise($row['modalite_evaluation'])
                    ->setModalitesEvaluationPedagogique($row['modalite_evaluation_pedagogique'])
                    ->setModalitesEncadrement($row['modalite_encadrement'])
                    ->setDocumentsRendre($row['document_rendre'])
                    ->setCommentaireLibre($row['texte_libre']);

                if ($isNew) {
                    $this->entityManager->persist($entity);
                    ++$created;
                } else {
                    ++$updated;
                }
            } catch (\Throwable $e) {
                ++$failed;
                if (count($messages) < 20) {
                    $messages[] = sprintf('StagePeriode #%s: %s', $row['id'], $e->getMessage());
                }
            }

            ++$processed;
            $context->advanceProgress();
            if (0 === $processed % self::BATCH_SIZE) {
                $this->flushAndClear($context);
            }
        }

        $this->flushAndClear($context);
        $this->finishProgress($context);

        return new MigrationResult($created, $updated, $skipped, $failed, $messages);
    }
}
