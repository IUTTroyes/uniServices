<?php

namespace App\DataProvider\Evaluation;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Etudiant\EtudiantNote;
use App\Entity\Scolarite\ScolEvaluation;
use App\Repository\EtudiantNoteRepository;
use Doctrine\ORM\EntityManagerInterface;

class EtudiantNotePersistProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EtudiantNoteRepository $noteRepository,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // Let Doctrine persist the note
        if ($data instanceof EtudiantNote) {
            $this->em->persist($data);
            $this->em->flush();

            // Recalculate evaluation state after note change
            $evaluation = $data->getEvaluation();
            if ($evaluation instanceof ScolEvaluation) {
                $this->refreshEvaluationEtat($evaluation);
            }
        }

        return $data;
    }

    private function refreshEvaluationEtat(ScolEvaluation $evaluation): void
    {
        $total = $this->noteRepository->countByEvaluation($evaluation);
        if ($total <= 0) {
            // fallback when nothing to evaluate
            $evaluation->setEtat('planifiee');
            $this->calcEvaluationStats($evaluation);
            $this->em->flush();
            return;
        }
        $completed = $this->noteRepository->countCompletedByEvaluation($evaluation);
        $newEtat = ($completed >= $total) ? 'complet' : 'planifiee';
        if ($evaluation->getEtat() !== $newEtat) {
            $evaluation->setEtat($newEtat);
        }

        $this->calcEvaluationStats($evaluation);
        $this->em->flush();
    }

    private function calcEvaluationStats(ScolEvaluation $evaluation): void
    {
        $values = [];
        foreach ($evaluation->getNotes() as $note) {
            if (!$note instanceof EtudiantNote) {
                continue;
            }
            $n = $note->getNote();
            // ignore null notes and justified absences/dispenses (do not include in stats)
            if (null === $n) {
                continue;
            }
            $ps = $note->getPresenceStatut();
            if (in_array($ps, [EtudiantNote::STATUT_ABSENT_JUSTIFIE, EtudiantNote::STATUT_DISPENSE], true)) {
                continue;
            }
            $values[] = (float) $n;
        }

        if (empty($values)) {
            $stats = ['moyenne' => 0, 'mediane' => 0, 'min' => 0, 'max' => 0];
            $evaluation->setStats($stats);
            return;
        }

        sort($values, SORT_NUMERIC);
        $count = count($values);
        $sum = array_sum($values);
        $moyenne = round($sum / $count, 2);

        if ($count % 2 === 1) {
            $mediane = $values[intdiv($count, 2)];
        } else {
            $mid = $count / 2;
            $mediane = ($values[$mid - 1] + $values[$mid]) / 2;
        }
        $mediane = round($mediane, 2);
        $min = round(min($values), 2);
        $max = round(max($values), 2);

        $stats = [
            'moyenne' => $moyenne,
            'mediane' => $mediane,
            'min' => $min,
            'max' => $max,
        ];

        $evaluation->setStats($stats);
    }
}
