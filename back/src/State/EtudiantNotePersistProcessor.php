<?php

namespace App\State;

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
            $this->em->flush();
            return;
        }
        $completed = $this->noteRepository->countCompletedByEvaluation($evaluation);
        $newEtat = ($completed >= $total) ? 'complet' : 'planifiee';
        if ($evaluation->getEtat() !== $newEtat) {
            $evaluation->setEtat($newEtat);
            $this->em->flush();
        }
    }
}
