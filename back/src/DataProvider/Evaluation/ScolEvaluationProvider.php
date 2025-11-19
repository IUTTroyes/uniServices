<?php

namespace App\DataProvider\Evaluation;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\EtudiantNoteRepository;
use App\Repository\EtudiantRepository;
use App\Repository\ScolEvaluationRepository;
use App\ApiDto\Evaluation\ScolEvaluationDto;

class ScolEvaluationProvider implements ProviderInterface
{
    public function __construct(
        private CollectionProvider $collectionProvider,
        private ItemProvider $itemProvider,
        private ScolEvaluationRepository $scolEvaluationRepository,
        private EtudiantRepository $etudiantRepository,
        private EtudiantNoteRepository $etudiantNoteRepository,
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $data = $this->collectionProvider->provide($operation, $uriVariables, $context);

        if (empty($data)) {
            return [];
        }

        $output = [];
        $enseignementTotals = []; // ['saisies'=>int,'total'=>int,'enseignement'=>object]

        foreach ($data as $evaluation) {
            $notes = $evaluation->getNotes();
            $total = count($notes);
            $notesSaisies = 0;
            foreach ($notes as $note) {
                if ($note->getNote() !== null) {
                    $notesSaisies++;
                }
            }

            $evalCompletion = $total > 0 ? round(($notesSaisies / $total) * 100, 2) : 0;
            $dto = $this->toDto($evaluation);
            $dto->setCompletion($evalCompletion);
            $output[] = $dto;

            $enseignement = $evaluation->getEnseignement();
            if ($enseignement && method_exists($enseignement, 'getId')) {
                $eid = $enseignement->getId();
                if (!isset($enseignementTotals[$eid])) {
                    $enseignementTotals[$eid] = [
                        'saisies' => 0,
                        'total' => 0,
                        'enseignement' => $enseignement,
                    ];
                }
                $enseignementTotals[$eid]['saisies'] += $notesSaisies;
                $enseignementTotals[$eid]['total'] += $total;
            }
        }

        $enseignementCompletion = [];
        foreach ($enseignementTotals as $eid => $t) {
            $enseignementCompletion[$eid] = $t['total'] > 0 ? round(($t['saisies'] / $t['total']) * 100, 2) : 0;
        }

        return [
            'evaluations' => $output,
            'enseignement_completion' => $enseignementCompletion,
        ];
    }

    public function toDto($evaluation)
    {
        $dtoEval = new ScolEvaluationDto();
        $dtoEval->setLibelle($evaluation->getLibelle());
        $dtoEval->setCommentaire($evaluation->getCommentaire());
        $dtoEval->setCoeff($evaluation->getCoeff());
        $dtoEval->setDate($evaluation->getDate());
        $dtoEval->setVisible($evaluation->isVisible());
        $dtoEval->setModifiable($evaluation->isModifiable());
        $dtoEval->setType($evaluation->getType());
        $dtoEval->setPersonnelAutorise($evaluation->getPersonnelAutorise());
        $dtoEval->setAnneeUniversitaire($evaluation->getAnneeUniversitaire());
        $dtoEval->setEnseignement($evaluation->getEnseignement());
        $dtoEval->setSemestre($evaluation->getSemestre());
        $dtoEval->setNotes($evaluation->getNotes());
        $dtoEval->setTypeGroupe($evaluation->getTypeGroupe());
        $dtoEval->setEtat($evaluation->getEtat());

        return $dtoEval;
    }
}
