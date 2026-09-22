<?php

namespace StageBundle\Migration\IntranetV3;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;
use App\ValueObject\Adresse;
use StageBundle\Entity\Stages\Contact;
use StageBundle\Entity\Stages\Entreprise;
use StageBundle\Entity\Stages\StageContexte;
use StageBundle\Entity\Stages\StageEtudiant;
use StageBundle\Entity\Stages\StagePeriode;
use StageBundle\Enum\EtatStageEnum;
use StageBundle\Enum\TypeStageEnum;
use Symfony\Component\Uid\Uuid;

final class StageEtudiantMigrator extends AbstractMigrator
{
    public function getName(): string { return 'stage-etudiants'; }
    public function getDependencies(): array { return [StagePeriodeMigrator::class, EntrepriseMigrator::class]; }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $created = $updated = $skipped = $failed = $processed = 0;
        $messages = []; $unknownStates = []; $invalidUuids = 0; $withoutPeriod = 0;
        $specialContexts = []; $missingCompanies = 0; $missingTutors = 0;
        $total = (int) $this->source->fetchOne('SELECT COUNT(*) FROM stage_etudiant');
        $this->startProgress($context, 'Stages étudiants', $total);
        $sql = <<<'SQL'
SELECT se.id, se.uuid, se.stage_periode_id, se.etudiant_id, se.entreprise_id, se.tuteur_id,
       se.service_stage_entreprise, se.sujet_stage, se.date_depot_formulaire, se.date_validation,
       se.date_convention_envoyee, se.date_convention_recu, se.etat_stage, se.date_debut_stage,
       se.date_fin_stage, se.activites, se.amenagement_stage, se.gratification,
       se.gratification_montant, se.gratification_periode, se.avantages, se.duree_hebdomadaire,
       se.duree_jours_stage, se.tuteur_universitaire_id, se.date_autorise, se.date_imprime,
       se.adresse_stage_id, se.periodes_interruptions, se.commentaire_duree_hebdomadaire,
       a.adresse1, a.adresse2, a.adresse3, a.code_postal, a.ville, a.pays
FROM stage_etudiant se
LEFT JOIN adresse a ON a.id = se.adresse_stage_id
ORDER BY se.id
SQL;
        foreach ($this->source->executeQuery($sql)->iterateAssociative() as $row) {
            try {
                $stagePeriode = null;
                if (null !== $row['stage_periode_id']) $stagePeriode = $this->entityManager->getRepository(StagePeriode::class)->findOneBy(['oldId' => (int) $row['stage_periode_id']]);
                else ++$withoutPeriod;

                $etudiant = $this->entityManager->getRepository(Etudiant::class)->findOneBy(['oldId' => (int) $row['etudiant_id']]);
                if (null === $etudiant) { ++$skipped; if (count($messages) < 20) $messages[] = sprintf('StageEtudiant #%s: étudiant V3 #%s introuvable.', $row['id'], $row['etudiant_id'] ?? 'null'); ++$processed; $context->advanceProgress(); continue; }
                if (null !== $row['stage_periode_id'] && null === $stagePeriode) { ++$skipped; if (count($messages) < 20) $messages[] = sprintf('StageEtudiant #%s: période V3 #%s introuvable.', $row['id'], $row['stage_periode_id']); ++$processed; $context->advanceProgress(); continue; }

                $uuid = self::legacyUuid($row['uuid']) ?? \Symfony\Component\Uid\Uuid::v4();
                $entity = $this->entityManager->getRepository(StageEtudiant::class)->findOneBy(['uuid' => $uuid]);
                $isNew = null === $entity;
                $entity ??= new StageEtudiant(self::nullableFloat($row['gratification_montant']));
                $entity->setUuid($uuid);

                $tuteurUniversitaire = null;
                if (null !== $row['tuteur_universitaire_id']) $tuteurUniversitaire = $this->entityManager->getRepository(Personnel::class)->findOneBy(['oldId' => (int) $row['tuteur_universitaire_id']]);
                $entreprise = null;
                if (null !== $row['entreprise_id']) { $entreprise = $this->entityManager->getRepository(Entreprise::class)->findOneBy(['oldId' => (int) $row['entreprise_id']]); if (null === $entreprise) ++$missingCompanies; }
                $tuteurEntreprise = null;
                if (null !== $row['tuteur_id']) { $tuteurEntreprise = $this->entityManager->getRepository(Contact::class)->findOneBy(['oldId' => (int) $row['tuteur_id']]); if (null === $tuteurEntreprise) ++$missingTutors; }

                [$state, $type] = self::stageStateAndType((string) $row['etat_stage']);
                if (null === $state) { $unknownStates[(string) $row['etat_stage']] = ($unknownStates[(string) $row['etat_stage']] ?? 0) + 1; $state = EtatStageEnum::AUTORISE; }

                $entity->setStagePeriode($stagePeriode)->setEtudiant($etudiant)->setEntreprise($entreprise)->setTuteur($tuteurEntreprise)
                    ->setServiceStageEntreprise($row['service_stage_entreprise'])->setSujetStage($row['sujet_stage'])
                    ->setDateDepotFormulaire(self::date($row['date_depot_formulaire']))->setDateValidation(self::date($row['date_validation']))->setDateConventionEnvoyee(self::date($row['date_convention_envoyee']))->setDateConventionRecu(self::date($row['date_convention_recu']))
                    ->setEtatStage($state)->setDateDebutStage(self::date($row['date_debut_stage']))->setDateFinStage(self::date($row['date_fin_stage']))->setActivites($row['activites'])->setAmenagementStage($row['amenagement_stage'])
                    ->setGratification((bool) $row['gratification'])->setGratificationMontant(self::nullableFloat($row['gratification_montant']))->setGratificationPeriode($row['gratification_periode'])->setAvantages($row['avantages'])
                    ->setDureeHebdomadaire((float) ($row['duree_hebdomadaire'] ?? 35))->setDureeJoursStage((int) ($row['duree_jours_stage'] ?? 0))->setTuteurUniversitaire($tuteurUniversitaire)
                    ->setDateAutorise(self::date($row['date_autorise']))->setDateImprime(self::date($row['date_imprime']))->setPeriodesInterruptions($row['periodes_interruptions'])->setCommentaireDureeHebdomadaire($row['commentaire_duree_hebdomadaire'])->setAdresseStage(self::adresse($row));

                if ($isNew) { $this->entityManager->persist($entity); ++$created; } else { ++$updated; }
                if (null !== $type) {
                    $stageContext = $this->entityManager->getRepository(StageContexte::class)->findOneBy(['stageEtudiant' => $entity]);
                    $stageContext ??= (new StageContexte())->setStageEtudiant($entity);
                    $stageContext->setType($type); $this->entityManager->persist($stageContext);
                    $specialContexts[$type->value] = ($specialContexts[$type->value] ?? 0) + 1;
                }
            } catch (\Throwable $e) { ++$failed; if (count($messages) < 20) $messages[] = sprintf('StageEtudiant #%s: %s', $row['id'], $e->getMessage()); }
            ++$processed; $context->advanceProgress(); if (0 === $processed % self::BATCH_SIZE) $this->flushAndClear($context);
        }
        $this->flushAndClear($context); $this->finishProgress($context);
        if ($withoutPeriod > 0) $messages[] = sprintf('%d stages sans période V3 importés avec stagePeriode=null.', $withoutPeriod);
        if ($missingCompanies > 0) $messages[] = sprintf('%d références entreprise V3 n’ont pas pu être résolues.', $missingCompanies);
        if ($missingTutors > 0) $messages[] = sprintf('%d références tuteur entreprise V3 n’ont pas pu être résolues.', $missingTutors);
        if ($invalidUuids > 20) $messages[] = sprintf('%d StageEtudiant avec UUID V3 absent ou invalide au total.', $invalidUuids);
        if ([] !== $specialContexts) { ksort($specialContexts); $parts = []; foreach ($specialContexts as $type => $count) $parts[] = sprintf('%s=%d', $type, $count); $messages[] = 'Contextes historiques V3 préservés séparément: '.implode(', ', $parts).'.'; }
        if ([] !== $unknownStates) { ksort($unknownStates); $parts = []; foreach ($unknownStates as $state => $count) $parts[] = sprintf('%s=%d', '' !== $state ? $state : '(vide)', $count); $messages[] = 'États V3 réellement inconnus, importés comme AUTORISE: '.implode(', ', $parts).'.'; }
        return new MigrationResult($created, $updated, $skipped, $failed, $messages);
    }

    /** @return array{?EtatStageEnum, ?TypeStageEnum} */
    private static function stageStateAndType(string $legacyState): array
    {
        $state = EtatStageEnum::tryFrom($legacyState); if (null !== $state) return [$state, null];
        return match ($legacyState) { 'ETAT_STAGE_ERASMUS' => [EtatStageEnum::AUTORISE, TypeStageEnum::ERASMUS], 'ETAT_STAGE_ETRANGER' => [EtatStageEnum::AUTORISE, TypeStageEnum::ETRANGER], 'ETAT_STAGE_APPRENTISSAGE' => [EtatStageEnum::AUTORISE, TypeStageEnum::APPRENTISSAGE], default => [null, null] };
    }
    private static function legacyUuid(mixed $value): ?Uuid { if (!is_string($value) || '' === $value) return null; try { if (16 === strlen($value)) return Uuid::fromBinary($value); if (Uuid::isValid($value)) return Uuid::fromString($value); } catch (\Throwable) { return null; } return null; }
    private static function date(mixed $value): ?\DateTimeInterface { return empty($value) ? null : new \DateTime((string) $value); }
    private static function nullableFloat(mixed $value): ?float { return null === $value || '' === $value ? null : (float) $value; }
    private static function adresse(array $row): ?Adresse { return Adresse::fromArray(['adresse1' => $row['adresse1'] ?? null, 'adresse2' => $row['adresse2'] ?? null, 'adresse3' => $row['adresse3'] ?? null, 'code_postal' => $row['code_postal'] ?? null, 'ville' => $row['ville'] ?? null, 'pays' => $row['pays'] ?? null]); }
}
