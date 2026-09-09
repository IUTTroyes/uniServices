<?php

namespace IntranetBundle\Service\Absence;

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
        $justificatifs = $this->justificatifRepository->findByEtudiant($etudiant);

        $hasChanges = $this->lierAbsences($absences, $justificatifs);
        if ($hasChanges) {
            $this->entityManager->flush();
        }
    }

    public function reconcilierPourJustificatif(EtudiantAbsenceJustificatif $justificatif): void
    {
        $etudiant = $justificatif->getEtudiant();
        $debut = $justificatif->getDebut();
        $fin = $justificatif->getFin();

        if (!$etudiant || !$debut || !$fin) {
            return;
        }

        $absences = $this->absenceRepository->findWithoutJustificatifByEtudiantAndInterval($etudiant, $debut, $fin);

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
