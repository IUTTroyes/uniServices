<?php

namespace App\State\Processor\AnneeUniv;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Etudiant\EtudiantNote;
use App\Entity\Scolarite\ScolEvaluation;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Repository\EtudiantNoteRepository;
use App\Repository\Structure\StructureAnneeUniversitaireRepository;
use Doctrine\ORM\EntityManagerInterface;

class AnneeUnivInitProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly StructureAnneeUniversitaireRepository $anneeUniversitaireRepository,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // Persist les changements faits à l'année universitaire (Doctrine suivra l'entité)
        $this->em->flush();

        if (!$data instanceof StructureAnneeUniversitaire) {
            return $data;
        }

        // si l'année universitaire nouvelle est active, alors on met les autres à inactif
        if ($data->isActif()) {
            $this->anneeUniversitaireRepository->setAllAnneeUnivInactifExcept($data);
        }

        // si c'est la création de l'année universitaire, alors on peut lancer la synchro via oréof pour tous les diplômes du département
        if (null === $data->getId()) {
        // lancer la synchro via oréof pour tous les diplômes du département

        }


        return $data;
    }
}
