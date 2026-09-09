<?php

namespace IntranetBundle\State\Processor\Absence;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use IntranetBundle\Entity\Etudiant\EtudiantAbsence;
use IntranetBundle\Service\Absence\JustificatifReconciliationService;

class EtudiantAbsenceCreateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly JustificatifReconciliationService $reconciliationService,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof EtudiantAbsence) {
            return $data;
        }

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        $etudiant = $data->getScolariteSemestre()?->getScolarite()?->getEtudiant();
        if (null !== $etudiant) {
            $this->reconciliationService->reconcilierPourEtudiant($etudiant);
        }

        return $data;
    }
}
