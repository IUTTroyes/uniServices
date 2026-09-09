<?php

namespace IntranetBundle\State\Processor\Absence;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use IntranetBundle\Entity\Etudiant\EtudiantAbsenceJustificatif;
use IntranetBundle\Service\Absence\JustificatifReconciliationService;

class EtudiantAbsenceJustificatifCreateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly JustificatifReconciliationService $reconciliationService,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof EtudiantAbsenceJustificatif) {
            return $data;
        }

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        $this->reconciliationService->reconcilierPourJustificatif($data);

        return $data;
    }
}
