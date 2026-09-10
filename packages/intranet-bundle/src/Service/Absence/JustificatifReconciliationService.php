<?php

namespace IntranetBundle\Service\Absence;

use App\Entity\Etudiant\EtudiantScolariteSemestre;
use App\Entity\Users\Etudiant;
use Doctrine\ORM\EntityManagerInterface;
use IntranetBundle\Entity\Etudiant\EtudiantAbsence;
use IntranetBundle\Entity\Etudiant\EtudiantAbsenceJustificatif;
use IntranetBundle\Repository\Etudiant\EtudiantAbsenceJustificatifRepository;
use IntranetBundle\Repository\Etudiant\EtudiantAbsenceRepository;

class JustificatifReconciliationService
{
    public function __construct(
        private readonly EtudiantAbsenceRepository $absenceRepository,
        private readonly EtudiantAbsenceJustificatifRepository $justificatifRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function reconcilierPourEtudiant(Etudiant $etudiant): void
    {
        $absences = $this->absenceRepository->findWithoutJustificatifByEtudiant($etudiant);
        $justificatifs = [];

        foreach ($etudiant->getScolarites() as $etudiantScolarite) {
            foreach ($etudiantScolarite->getScolariteSemestre() as $scolariteSemestre) {
                if (!$scolariteSemestre instanceof EtudiantScolariteSemestre) {
                    continue;
                }

                $justificatifs = [...$justificatifs, ...$this->justificatifRepository->findByScolariteSemestre($scolariteSemestre)];
            }
        }

        $hasChanges = $this->lierAbsences($absences, $justificatifs);
        if ($hasChanges) {
            $this->entityManager->flush();
        }
    }

    public function reconcilierPourJustificatif(EtudiantAbsenceJustificatif $justificatif): void
    {
        $scolariteSemestre = $justificatif->getScolariteSemestre();
        $debut = $justificatif->getDebut();
        $fin = $justificatif->getFin();

        if (!$scolariteSemestre || !$debut || !$fin) {
            return;
        }

        $etudiant = $scolariteSemestre->getScolarite()?->getEtudiant();
        if (!$etudiant) {
            return;
        }

        $absences = array_filter(
            $this->absenceRepository->findWithoutJustificatifByEtudiantAndInterval($etudiant, $debut, $fin),
            static fn (EtudiantAbsence $absence): bool => $absence->getScolariteSemestre()?->getId() === $scolariteSemestre->getId(),
        );

        $hasChanges = $this->lierAbsences($absences, [$justificatif]);
        if ($hasChanges) {
            $this->entityManager->flush();
        }
    }

    /**
     * @param EtudiantAbsence[] $absences
     * @param EtudiantAbsenceJustificatif[] $justificatifs
     */
    private function lierAbsences(array $absences, array $justificatifs): bool
    {
        $hasChanges = false;

        foreach ($absences as $absence) {
            if (!$absence instanceof EtudiantAbsence || null !== $absence->getAbsenceJustificatif()) {
                continue;
            }

            $event = $absence->getEvent();
            $eventDebut = $event?->getDebut();
            $eventFin = $event?->getFin();
            if (!$eventDebut || !$eventFin) {
                continue;
            }

            foreach ($justificatifs as $justificatif) {
                if (!$justificatif->couvre($eventDebut, $eventFin)) {
                    continue;
                }

                $absence->setAbsenceJustificatif($justificatif);
                $hasChanges = true;
                break;
            }
        }

        return $hasChanges;
    }
}
