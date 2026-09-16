<?php

namespace StageBundle\Migration\IntranetV3;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;
use App\Migration\IntranetV3\Users\EtudiantMigrator;
use App\Migration\IntranetV3\Users\PersonnelMigrator;
use App\ValueObject\Adresse;
use StageBundle\Entity\Stages\StageEtudiant;
use StageBundle\Entity\Stages\StagePeriode;
use StageBundle\Enum\EtatStageEnum;
use Symfony\Component\Uid\Uuid;

final class StageEtudiantMigrator extends AbstractMigrator
{
    public function getName(): string
    {
        return 'stage-etudiants';
    }

    public function getDependencies(): array
    {
        return [StagePeriodeMigrator::class, EtudiantMigrator::class, PersonnelMigrator::class];
    }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $created = $updated = $skipped = $failed = $processed = 0;
        $messages = [];
        $unknownStates = [];
        $total = (int) $this->source->fetchOne('SELECT COUNT(*) FROM stage_etudiant');
        $this->startProgress($context, 'Stages étudiants', $total);

        $sql = <<<'SQL'
SELECT se.id, se.uuid, se.stage_periode_id, se.etudiant_id, se.service_stage_entreprise,
       se.sujet_stage, se.date_depot_formulaire, se.date_validation, se.date_convention_envoyee,
       se.date_convention_recu, se.etat_stage, se.date_debut_stage, se.date_fin_stage,
       se.activites, se.amenagement_stage, se.gratification, se.gratification_montant,
       se.gratification_periode, se.avantages, se.duree_hebdomadaire, se.duree_jours_stage,
       se.tuteur_universitaire_id, se.date_autorise, se.date_imprime, se.adresse_stage_id,
       se.periodes_interruptions, se.commentaire_duree_hebdomadaire,
       a.adresse1, a.adresse2, a.adresse3, a.code_postal, a.ville, a.pays
FROM stage_etudiant se
LEFT JOIN adresse a ON a.id = se.adresse_stage_id
ORDER BY se.id
SQL;

        foreach ($this->source->executeQuery($sql)->iterateAssociative() as $row) {
            try {
                $stagePeriode = $this->entityManager->getRepository(StagePeriode::class)
                    ->findOneBy(['oldId' => (int) $row['stage_periode_id']]);
                $etudiant = $this->entityManager->getRepository(Etudiant::class)
                    ->findOneBy(['oldId' => (int) $row['etudiant_id']]);

                if (null === $stagePeriode || null === $etudiant) {
                    ++$skipped;
                    if (count($messages) < 20) {
                        $messages[] = sprintf(
                            'StageEtudiant #%s: référence obligatoire introuvable (période=%s, étudiant=%s).',
                            $row['id'],
                            $row['stage_periode_id'] ?? 'null',
                            $row['etudiant_id'] ?? 'null',
                        );
                    }
                    ++$processed;
                    $context->advanceProgress();
                    continue;
                }

                if (empty($row['uuid']) || !Uuid::isValid((string) $row['uuid'])) {
                    ++$skipped;
                    if (count($messages) < 20) {
                        $messages[] = sprintf('StageEtudiant #%s: UUID V3 absent ou invalide.', $row['id']);
                    }
                    ++$processed;
                    $context->advanceProgress();
                    continue;
                }

                $uuid = Uuid::fromString((string) $row['uuid']);
                $entity = $this->entityManager->getRepository(StageEtudiant::class)->findOneBy(['uuid' => $uuid]);
                $isNew = null === $entity;
                $entity ??= new StageEtudiant(self::nullableFloat($row['gratification_montant']));
                $entity->setUuid($uuid);

                $tuteurUniversitaire = null;
                if (null !== $row['tuteur_universitaire_id']) {
                    $tuteurUniversitaire = $this->entityManager->getRepository(Personnel::class)
                        ->findOneBy(['oldId' => (int) $row['tuteur_universitaire_id']]);
                }

                $state = EtatStageEnum::tryFrom((string) $row['etat_stage']);
                if (null === $state) {
                    $unknownStates[(string) $row['etat_stage']] = ($unknownStates[(string) $row['etat_stage']] ?? 0) + 1;
                    $state = EtatStageEnum::AUTORISE;
                }

                $entity
                    ->setStagePeriode($stagePeriode)
                    ->setEtudiant($etudiant)
                    ->setServiceStageEntreprise($row['service_stage_entreprise'])
                    ->setSujetStage($row['sujet_stage'])
                    ->setDateDepotFormulaire(self::dateTime($row['date_depot_formulaire']))
                    ->setDateValidation(self::dateTime($row['date_validation']))
                    ->setDateConventionEnvoyee(self::dateTime($row['date_convention_envoyee']))
                    ->setDateConventionRecu(self::dateTime($row['date_convention_recu']))
                    ->setEtatStage($state)
                    ->setDateDebutStage(self::date($row['date_debut_stage']))
                    ->setDateFinStage(self::date($row['date_fin_stage']))
                    ->setActivites($row['activites'])
                    ->setAmenagementStage($row['amenagement_stage'])
                    ->setGratification((bool) $row['gratification'])
                    ->setGratificationMontant(self::nullableFloat($row['gratification_montant']))
                    ->setGratificationPeriode($row['gratification_periode'])
                    ->setAvantages($row['avantages'])
                    ->setDureeHebdomadaire((float) ($row['duree_hebdomadaire'] ?? 35))
                    ->setDureeJoursStage((int) ($row['duree_jours_stage'] ?? 0))
                    ->setTuteurUniversitaire($tuteurUniversitaire)
                    ->setDateAutorise(self::dateTime($row['date_autorise']))
                    ->setDateImprime(self::dateTime($row['date_imprime']))
                    ->setPeriodesInterruptions($row['periodes_interruptions'])
                    ->setCommentaireDureeHebdomadaire($row['commentaire_duree_hebdomadaire'])
                    ->setAdresseStage(self::adresse($row));

                if ($isNew) {
                    $this->entityManager->persist($entity);
                    ++$created;
                } else {
                    ++$updated;
                }
            } catch (\Throwable $e) {
                ++$failed;
                if (count($messages) < 20) {
                    $messages[] = sprintf('StageEtudiant #%s: %s', $row['id'], $e->getMessage());
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

        if ([] !== $unknownStates) {
            ksort($unknownStates);
            $parts = [];
            foreach ($unknownStates as $state => $count) {
                $parts[] = sprintf('%s=%d', '' !== $state ? $state : '(vide)', $count);
            }
            $messages[] = 'États V3 non reconnus, importés comme AUTORISE: ' . implode(', ', $parts) . '.';
        }

        return new MigrationResult($created, $updated, $skipped, $failed, $messages);
    }

    private static function date(mixed $value): ?\DateTimeInterface
    {
        return empty($value) ? null : new \DateTime((string) $value);
    }

    private static function dateTime(mixed $value): ?\DateTimeInterface
    {
        return self::date($value);
    }

    private static function nullableFloat(mixed $value): ?float
    {
        return null === $value || '' === $value ? null : (float) $value;
    }

    /** @param array<string, mixed> $row */
    private static function adresse(array $row): ?Adresse
    {
        return Adresse::fromArray([
            'adresse1' => $row['adresse1'] ?? null,
            'adresse2' => $row['adresse2'] ?? null,
            'adresse3' => $row['adresse3'] ?? null,
            'code_postal' => $row['code_postal'] ?? null,
            'ville' => $row['ville'] ?? null,
            'pays' => $row['pays'] ?? null,
        ]);
    }
}
