<?php

namespace App\Controller\Stage;

use App\Entity\Users\Personnel;
use Doctrine\ORM\EntityManagerInterface;
use StageBundle\Entity\Stages\StageEtudiant;
use StageBundle\Entity\Stages\StageSoutenance;
use StageBundle\Entity\Stages\StagePeriode;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StageSoutenanceController extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    private function getIdFromIri($id): ?int
    {
        if ($id === null || $id === '') {
            return null;
        }
        if (is_string($id) && str_contains($id, '/')) {
            $parts = explode('/', $id);
            return (int)end($parts);
        }
        return (int)$id;
    }

    #[Route('/api/stage_soutenances/validate-conflict', name: 'api_stage_soutenances_validate_conflict', methods: ['POST'])]
    public function validateConflict(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $stageEtudiantId = $this->getIdFromIri($data['stageEtudiantId'] ?? null);
        $enseignantJuryId = $this->getIdFromIri($data['enseignantJuryId'] ?? null);
        $dateStr = $data['dateSoutenance'] ?? null;
        $duree = (int)($data['duree'] ?? 30);
        $soutenanceId = $this->getIdFromIri($data['soutenanceId'] ?? null);
        $stagePeriodeId = $this->getIdFromIri($data['stagePeriodeId'] ?? null);

        if (!$dateStr || (!$stageEtudiantId && !$stagePeriodeId)) {
            return new JsonResponse(['error' => 'Paramètres requis manquants.'], Response::HTTP_BAD_REQUEST);
        }

        $stageEtudiant = $stageEtudiantId ? $this->em->getRepository(StageEtudiant::class)->find($stageEtudiantId) : null;
        $stagePeriode = $stagePeriodeId ? $this->em->getRepository(StagePeriode::class)->find($stagePeriodeId) : ($stageEtudiant ? $stageEtudiant->getStagePeriode() : null);

        if (!$stagePeriode) {
            return new JsonResponse(['error' => 'Période de stage non trouvée.'], Response::HTTP_NOT_FOUND);
        }

        $enseignantJury = $enseignantJuryId ? $this->em->getRepository(Personnel::class)->find($enseignantJuryId) : null;
        $start = new \DateTime($dateStr);
        $end = (clone $start)->modify("+$duree minutes");

        $conflicts = $this->checkConflicts($stagePeriode, $stageEtudiant, $enseignantJury, $start, $end, $soutenanceId);

        return new JsonResponse([
            'hasConflict' => count($conflicts) > 0,
            'messages' => $conflicts
        ]);
    }

    #[Route('/api/stage_soutenances/save', name: 'api_stage_soutenances_save', methods: ['POST'])]
    public function save(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $soutenanceId = $this->getIdFromIri($data['id'] ?? null);
        $stageEtudiantId = $this->getIdFromIri($data['stageEtudiantId'] ?? null);
        $enseignantJuryId = $this->getIdFromIri($data['enseignantJuryId'] ?? null);
        $dateStr = $data['dateSoutenance'] ?? null;
        $salle = $data['salle'] ?? '';
        $duree = (int)($data['duree'] ?? 30);
        $stagePeriodeId = $this->getIdFromIri($data['stagePeriodeId'] ?? null);

        if (!$dateStr) {
            return new JsonResponse(['error' => 'Paramètres requis manquants (date).'], Response::HTTP_BAD_REQUEST);
        }

        $stageEtudiant = $stageEtudiantId ? $this->em->getRepository(StageEtudiant::class)->find($stageEtudiantId) : null;
        $stagePeriode = $stagePeriodeId ? $this->em->getRepository(StagePeriode::class)->find($stagePeriodeId) : ($stageEtudiant ? $stageEtudiant->getStagePeriode() : null);

        if (!$stagePeriode) {
            return new JsonResponse(['error' => 'Période de stage non trouvée.'], Response::HTTP_NOT_FOUND);
        }

        $enseignantJury = $enseignantJuryId ? $this->em->getRepository(Personnel::class)->find($enseignantJuryId) : null;
        $start = new \DateTime($dateStr);
        $end = (clone $start)->modify("+$duree minutes");

        // Validate conflicts
        $conflicts = $this->checkConflicts($stagePeriode, $stageEtudiant, $enseignantJury, $start, $end, $soutenanceId);
        if (count($conflicts) > 0) {
            return new JsonResponse([
                'error' => 'Conflit de planification détecté.',
                'messages' => $conflicts
            ], Response::HTTP_CONFLICT);
        }

        // Save soutenance slot
        if ($soutenanceId) {
            $soutenance = $this->em->getRepository(StageSoutenance::class)->find($soutenanceId);
            if (!$soutenance) {
                return new JsonResponse(['error' => 'Créneau non trouvé.'], Response::HTTP_NOT_FOUND);
            }
        } else {
            $soutenance = new StageSoutenance();
        }

        // If assigning a student, verify they don't already have another defense
        if ($stageEtudiant) {
            $existing = $this->em->getRepository(StageSoutenance::class)->findOneBy(['stageEtudiant' => $stageEtudiant]);
            if ($existing && $existing->getId() !== $soutenance->getId()) {
                return new JsonResponse([
                    'error' => 'L\'étudiant a déjà une soutenance planifiée sur un autre créneau.'
                ], Response::HTTP_CONFLICT);
            }
        }

        $soutenance->setStagePeriode($stagePeriode)
            ->setStageEtudiant($stageEtudiant)
            ->setEnseignantJury($enseignantJury)
            ->setDateSoutenance($start)
            ->setSalle($salle)
            ->setDuree($duree);

        $this->em->persist($soutenance);
        $this->em->flush();

        return new JsonResponse([
            'success' => true,
            'id' => $soutenance->getId()
        ]);
    }

    #[Route('/api/stage_soutenances/bulk-generate', name: 'api_stage_soutenances_bulk_generate', methods: ['POST'])]
    public function bulkGenerate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $stagePeriodeId = $this->getIdFromIri($data['stagePeriodeId'] ?? null);
        $dateStr = $data['date'] ?? null; // YYYY-MM-DD
        $startTimeStr = $data['startTime'] ?? null; // HH:MM
        $endTimeStr = $data['endTime'] ?? null; // HH:MM
        $duree = (int)($data['duree'] ?? 30);
        $pauseDuree = (int)($data['pauseDuree'] ?? 0);
        $salle = $data['salle'] ?? '';

        if (!$stagePeriodeId || !$dateStr || !$startTimeStr || !$endTimeStr) {
            return new JsonResponse(['error' => 'Paramètres requis manquants.'], Response::HTTP_BAD_REQUEST);
        }

        $periode = $this->em->getRepository(StagePeriode::class)->find($stagePeriodeId);
        if (!$periode) {
            return new JsonResponse(['error' => 'Période de stage non trouvée.'], Response::HTTP_NOT_FOUND);
        }

        $start = new \DateTime($dateStr . ' ' . $startTimeStr);
        $endLimit = new \DateTime($dateStr . ' ' . $endTimeStr);
        $slotsCreated = 0;

        while ($start < $endLimit) {
            $slotEnd = (clone $start)->modify("+$duree minutes");
            if ($slotEnd > $endLimit) {
                break;
            }

            $slot = new StageSoutenance();
            $slot->setStagePeriode($periode)
                ->setDateSoutenance(clone $start)
                ->setDuree($duree)
                ->setSalle($salle);

            $this->em->persist($slot);
            $slotsCreated++;

            $start = (clone $slotEnd)->modify("+$pauseDuree minutes");
        }

        $this->em->flush();

        return new JsonResponse([
            'success' => true,
            'slotsCreated' => $slotsCreated
        ]);
    }

    private function checkConflicts(
        StagePeriode $periode,
        ?StageEtudiant $stageEtudiant,
        ?Personnel $enseignantJury,
        \DateTime $start,
        \DateTime $end,
        ?int $soutenanceId
    ): array {
        // Get all soutenances for this period
        $allSoutenances = $this->em->getRepository(StageSoutenance::class)->createQueryBuilder('s')
            ->where('s.stagePeriode = :periode')
            ->setParameter('periode', $periode)
            ->getQuery()
            ->getResult();

        $conflicts = [];
        $tuteur = $stageEtudiant ? $stageEtudiant->getTuteurUniversitaire() : null;

        foreach ($allSoutenances as $s) {
            if ($soutenanceId && $s->getId() === (int)$soutenanceId) {
                continue;
            }

            $sStart = $s->getDateSoutenance();
            $sEnd = (clone $sStart)->modify("+" . $s->getDuree() . " minutes");

            // Overlap condition
            $overlap = max($start->getTimestamp(), $sStart->getTimestamp()) < min($end->getTimestamp(), $sEnd->getTimestamp());
            if ($overlap) {
                $sTuteur = $s->getStageEtudiant()?->getTuteurUniversitaire();
                $sAssesseur = $s->getEnseignantJury();
                $sStudentName = $s->getStageEtudiant()?->getEtudiant()?->getDisplay() ?? 'un autre créneau';

                // Check tuteur overlap
                if ($tuteur && ($tuteur === $sTuteur || $tuteur === $sAssesseur)) {
                    $conflicts[] = sprintf(
                        "Le tuteur universitaire (%s) a déjà une soutenance planifiée sur ce créneau (%s - %s) pour %s.",
                        $tuteur->getDisplay(),
                        $sStart->format('H:i'),
                        $sEnd->format('H:i'),
                        $sStudentName
                    );
                }

                // Check assesseur overlap
                if ($enseignantJury && ($enseignantJury === $sTuteur || $enseignantJury === $sAssesseur)) {
                    $conflicts[] = sprintf(
                        "L'enseignant assesseur (%s) a déjà une soutenance planifiée sur ce créneau (%s - %s) pour %s.",
                        $enseignantJury->getDisplay(),
                        $sStart->format('H:i'),
                        $sEnd->format('H:i'),
                        $sStudentName
                    );
                }

                // Check student overlap
                if ($stageEtudiant && $s->getStageEtudiant() === $stageEtudiant) {
                    $conflicts[] = sprintf(
                        "L'étudiant a déjà une soutenance planifiée sur ce créneau (%s - %s).",
                        $sStart->format('H:i'),
                        $sEnd->format('H:i')
                    );
                }
            }
        }

        return $conflicts;
    }
}
