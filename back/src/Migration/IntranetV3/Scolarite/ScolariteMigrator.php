<?php

namespace App\Migration\IntranetV3\Scolarite;

use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Etudiant\EtudiantScolariteSemestre;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructurePn;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Users\Etudiant;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;
use App\Migration\IntranetV3\Structure\AnneeUniversitaireMigrator;
use App\Migration\IntranetV3\Structure\SemestreMigrator;
use App\Migration\IntranetV3\Users\EtudiantMigrator;

final class ScolariteMigrator extends AbstractMigrator
{
    private const MAX_DIAGNOSTIC_SAMPLES = 20;

    public function getName(): string
    {
        return 'scolarites';
    }

    public function getDependencies(): array
    {
        return [EtudiantMigrator::class, AnneeUniversitaireMigrator::class, SemestreMigrator::class];
    }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $created = $updated = $skipped = $failed = $processed = 0;
        $messages = [];
        $diagnostics = [
            'etudiant' => 0,
            'anneeUniversitaire' => 0,
            'diplome' => 0,
            'pn' => 0,
            'semestre' => 0,
        ];
        $sampleCount = 0;

        $sql = <<<'SQL'
SELECT
    sc.id,
    sc.etudiant_id,
    sc.semestre_id,
    sc.annee_universitaire_id,
    a.diplome_id,
    sc.ordre,
    sc.moyenne,
    sc.nb_absences,
    sc.commentaire,
    sc.diffuse,
    SUM(sc.nb_absences) OVER (PARTITION BY sc.etudiant_id, sc.annee_universitaire_id) AS total_nb_absences,
    MAX(sc.diffuse) OVER (PARTITION BY sc.etudiant_id, sc.annee_universitaire_id) AS public_annee
FROM scolarite sc
INNER JOIN semestre s ON s.id = sc.semestre_id
INNER JOIN annee a ON a.id = s.annee_id
ORDER BY sc.annee_universitaire_id, sc.etudiant_id, sc.ordre, sc.id
SQL;

        foreach ($this->source->executeQuery($sql)->iterateAssociative() as $row) {
            try {
                $etudiantRepository = $this->entityManager->getRepository(Etudiant::class);
                $anneeRepository = $this->entityManager->getRepository(StructureAnneeUniversitaire::class);
                $diplomeRepository = $this->entityManager->getRepository(StructureDiplome::class);
                $pnRepository = $this->entityManager->getRepository(StructurePn::class);
                $scolariteRepository = $this->entityManager->getRepository(EtudiantScolarite::class);
                $scolariteSemestreRepository = $this->entityManager->getRepository(EtudiantScolariteSemestre::class);

                $etudiant = $etudiantRepository->findOneBy(['oldId' => (int) $row['etudiant_id']]);
                $anneeUniversitaire = $anneeRepository->findOneBy(['oldId' => (int) $row['annee_universitaire_id']]);
                $diplome = $diplomeRepository->findOneBy(['oldId' => (int) $row['diplome_id']]);
                $pn = null;
                $semestre = null;

                if (null !== $diplome && null !== $anneeUniversitaire) {
                    $pn = $pnRepository->findOneBy([
                        'diplome' => $diplome,
                        'anneeUniversitaire' => $anneeUniversitaire,
                    ]);
                }

                if (null !== $pn) {
                    $semestre = $this->entityManager->createQueryBuilder()
                        ->select('sem')
                        ->from(StructureSemestre::class, 'sem')
                        ->innerJoin('sem.annee', 'an')
                        ->andWhere('sem.oldId = :oldId')
                        ->andWhere('an.pn = :pn')
                        ->setParameter('oldId', (int) $row['semestre_id'])
                        ->setParameter('pn', $pn)
                        ->getQuery()
                        ->getOneOrNullResult();
                }

                $missing = [];
                if (null === $etudiant) {
                    ++$diagnostics['etudiant'];
                    $missing[] = sprintf('étudiant V3 #%s', $row['etudiant_id']);
                }
                if (null === $anneeUniversitaire) {
                    ++$diagnostics['anneeUniversitaire'];
                    $missing[] = sprintf('année universitaire V3 #%s', $row['annee_universitaire_id']);
                }
                if (null === $diplome) {
                    ++$diagnostics['diplome'];
                    $missing[] = sprintf('diplôme V3 #%s', $row['diplome_id']);
                }
                if (null !== $diplome && null !== $anneeUniversitaire && null === $pn) {
                    ++$diagnostics['pn'];
                    $missing[] = sprintf('PN snapshot diplôme #%s / année #%s', $row['diplome_id'], $row['annee_universitaire_id']);
                }
                if (null !== $pn && null === $semestre) {
                    ++$diagnostics['semestre'];
                    $missing[] = sprintf('semestre snapshot V3 #%s', $row['semestre_id']);
                }

                if ([] !== $missing) {
                    ++$skipped;
                    if ($sampleCount < self::MAX_DIAGNOSTIC_SAMPLES) {
                        $messages[] = sprintf('Scolarite #%s ignorée: %s.', $row['id'], implode(', ', $missing));
                        ++$sampleCount;
                    }
                    ++$processed;
                    $this->flushBatch($context, $processed);
                    continue;
                }

                $scolarite = $scolariteRepository->findOneBy([
                    'etudiant' => $etudiant,
                    'anneeUniversitaire' => $anneeUniversitaire,
                ]);

                if (null === $scolarite) {
                    $scolarite = new EtudiantScolarite();
                    $scolarite
                        ->setEtudiant($etudiant)
                        ->setAnneeUniversitaire($anneeUniversitaire)
                        ->setDepartement($semestre->getAnnee()?->getDepartement())
                        ->setOrdre((int) $row['ordre']);
                    $this->entityManager->persist($scolarite);
                    ++$created;
                } else {
                    ++$updated;
                }

                $scolarite
                    ->setNbAbsences((int) $row['total_nb_absences'])
                    ->setPublic((bool) $row['public_annee']);
                $scolarite->setActif($anneeUniversitaire->isActif() ?? false);

                if (null === $scolarite->getDepartement()) {
                    $scolarite->setDepartement($semestre->getAnnee()?->getDepartement());
                }

                $scolariteSemestre = $scolariteSemestreRepository->findOneBy([
                    'scolarite' => $scolarite,
                    'semestre' => $semestre,
                ]);

                if (null === $scolariteSemestre) {
                    $scolariteSemestre = new EtudiantScolariteSemestre();
                    $scolariteSemestre
                        ->setScolarite($scolarite)
                        ->setSemestre($semestre);
                    $this->entityManager->persist($scolariteSemestre);
                }

                $scolariteSemestre->setMoyenne(null !== $row['moyenne'] ? (float) $row['moyenne'] : null);
                $scolariteSemestre->setNbAbsences((int) ($row['nb_absences'] ?? 0));

                if (!empty($row['commentaire']) && empty($scolarite->getCommentaire())) {
                    $scolarite->setCommentaire((string) $row['commentaire']);
                }
            } catch (\Throwable $e) {
                ++$failed;
                if ($sampleCount < self::MAX_DIAGNOSTIC_SAMPLES) {
                    $messages[] = sprintf('Scolarite #%s: %s', $row['id'], $e->getMessage());
                    ++$sampleCount;
                }
            }

            ++$processed;
            $this->flushBatch($context, $processed);
        }

        if ($skipped > 0) {
            $messages[] = sprintf(
                'Résumé des références non résolues: étudiants=%d, années universitaires=%d, diplômes=%d, PN snapshots=%d, semestres snapshots=%d.',
                $diagnostics['etudiant'],
                $diagnostics['anneeUniversitaire'],
                $diagnostics['diplome'],
                $diagnostics['pn'],
                $diagnostics['semestre'],
            );
        }

        $this->flushAndClear($context);

        return new MigrationResult($created, $updated, $skipped, $failed, $messages);
    }
}
