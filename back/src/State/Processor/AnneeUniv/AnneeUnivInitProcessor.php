<?php

namespace App\State\Processor\AnneeUniv;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Etudiant\EtudiantNote;
use App\Entity\Scolarite\ScolEvaluation;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Repository\EtudiantNoteRepository;
use Doctrine\ORM\EntityManagerInterface;

class AnneeUnivInitProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // Persist les changements faits à l'année universitaire (Doctrine suivra l'entité)
        $this->em->flush();

        if (!$data instanceof StructureAnneeUniversitaire) {
            return $data;
        }

        // lancer la synchro via oréof pour tous les diplômes du département

        return $data;
    }
}
