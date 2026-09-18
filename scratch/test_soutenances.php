<?php

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Doctrine\ORM\EntityManagerInterface;
use StageBundle\Entity\Stages\StageEtudiant;
use StageBundle\Entity\Stages\StageSoutenance;
use App\Entity\Users\Personnel;

require_once __DIR__ . '/../back/vendor/autoload.php';

(new Dotenv())->bootEnv(__DIR__ . '/../back/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

/** @var EntityManagerInterface $em */
$em = $container->get('doctrine.orm.entity_manager');

echo "=== Démarrage de la vérification de la planification des soutenances ===\n";

// 1. Fetch some test records
$stages = $em->getRepository(StageEtudiant::class)->findAll();
if (count($stages) < 2) {
    echo "Pas assez de dossiers de stage pour tester. Veuillez charger les fixtures.\n";
    exit(1);
}

$stage1 = $stages[0];
$stage2 = $stages[1];

echo "Stage 1: Étudiant " . ($stage1->getEtudiant()?->getDisplay() ?? 'N/A') . " (Tuteur: " . ($stage1->getTuteurUniversitaire()?->getDisplay() ?? 'N/A') . ")\n";
echo "Stage 2: Étudiant " . ($stage2->getEtudiant()?->getDisplay() ?? 'N/A') . " (Tuteur: " . ($stage2->getTuteurUniversitaire()?->getDisplay() ?? 'N/A') . ")\n";

// Ensure they have different tuteurs or let's find the teachers
$personnels = $em->getRepository(Personnel::class)->findAll();
if (count($personnels) < 2) {
    echo "Pas assez d'enseignants pour le test.\n";
    exit(1);
}
$teacherA = $personnels[0];
$teacherB = $personnels[1];

// Clean existing test soutenances
$existing = $em->getRepository(StageSoutenance::class)->findAll();
foreach ($existing as $ex) {
    $em->remove($ex);
}
$em->flush();

echo "Nettoyage des soutenances précédentes effectué.\n";

// 2. Schedule a defense for Stage 1: Today at 14:00 for 30 minutes with Teacher B as Assesseur
$date1 = new \DateTime('2026-08-10 14:00:00');
$soutenance1 = new StageSoutenance();
$soutenance1->setStageEtudiant($stage1)
    ->setStagePeriode($stage1->getStagePeriode())
    ->setEnseignantJury($teacherB)
    ->setDateSoutenance($date1)
    ->setSalle('Salle A101')
    ->setDuree(30);

$em->persist($soutenance1);
$em->flush();

echo "Soutenance 1 planifiée pour " . $stage1->getEtudiant()?->getDisplay() . " à 14:00 (Durée: 30m).\n";

// 3. Test check conflict logic (replicating the logic in StageSoutenanceController)
function checkConflicts(
    EntityManagerInterface $em,
    StageEtudiant $stageEtudiant,
    ?Personnel $enseignantJury,
    \DateTime $start,
    \DateTime $end,
    ?int $soutenanceId
): array {
    $periode = $stageEtudiant->getStagePeriode();
    if (!$periode) {
        return [];
    }

    $allSoutenances = $em->getRepository(StageSoutenance::class)->createQueryBuilder('s')
        ->join('s.stageEtudiant', 'se')
        ->where('se.stagePeriode = :periode')
        ->setParameter('periode', $periode)
        ->getQuery()
        ->getResult();

    $conflicts = [];
    $tuteur = $stageEtudiant->getTuteurUniversitaire();

    foreach ($allSoutenances as $s) {
        if ($soutenanceId && $s->getId() === (int)$soutenanceId) {
            continue;
        }

        $sStart = $s->getDateSoutenance();
        $sEnd = (clone $sStart)->modify("+" . $s->getDuree() . " minutes");

        $overlap = max($start->getTimestamp(), $sStart->getTimestamp()) < min($end->getTimestamp(), $sEnd->getTimestamp());
        if ($overlap) {
            $sTuteur = $s->getStageEtudiant()?->getTuteurUniversitaire();
            $sAssesseur = $s->getEnseignantJury();
            $sStudentName = $s->getStageEtudiant()?->getEtudiant()?->getDisplay() ?? 'un autre étudiant';

            if ($tuteur && ($tuteur === $sTuteur || $tuteur === $sAssesseur)) {
                $conflicts[] = "Conflit Tuteur: " . $tuteur->getDisplay() . " est déjà mobilisé pour " . $sStudentName;
            }

            if ($enseignantJury && ($enseignantJury === $sTuteur || $enseignantJury === $sAssesseur)) {
                $conflicts[] = "Conflit Assesseur: " . $enseignantJury->getDisplay() . " est déjà mobilisé pour " . $sStudentName;
            }
        }
    }

    return $conflicts;
}

// 4. Scenario: Schedule Stage 2 at 14:15 (overlap) with Stage 1's Tuteur or Stage 1's Assesseur as the Jury
$testDate = new \DateTime('2026-08-10 14:15:00');
$testEnd = (clone $testDate)->modify("+30 minutes");

echo "\nTest 1: Planifier Stage 2 à 14:15 avec l'assesseur de Stage 1 (Teacher B) comme assesseur...\n";
$conflicts = checkConflicts($em, $stage2, $teacherB, $testDate, $testEnd, null);
if (count($conflicts) > 0) {
    echo "[SUCCÈS] Conflit détecté correctement:\n";
    foreach ($conflicts as $c) {
        echo " - $c\n";
    }
} else {
    echo "[ÉCHEC] Aucun conflit détecté!\n";
}

echo "\nTest 2: Planifier Stage 2 à 15:00 (pas de chevauchement) avec Teacher B...\n";
$okDate = new \DateTime('2026-08-10 15:00:00');
$okEnd = (clone $okDate)->modify("+30 minutes");
$conflicts = checkConflicts($em, $stage2, $teacherB, $okDate, $okEnd, null);
if (count($conflicts) === 0) {
    echo "[SUCCÈS] Pas de conflit détecté pour le créneau libre.\n";
} else {
    echo "[ÉCHEC] Conflit inattendu détecté:\n";
    foreach ($conflicts as $c) {
        echo " - $c\n";
    }
}

echo "\n=== Fin des vérifications ===\n";
