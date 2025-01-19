<?php

namespace App\Services\Edt;

use App\Entity\Edt\EdtEvent;
use App\Entity\Previsionnel\Previsionnel;
use Doctrine\ORM\EntityManagerInterface;

class GenereSlots
{
    private int $nbSlots = 0;
    private array $groupes = [];

    public function __construct(
        protected EntityManagerInterface $entityManager,
    )
    {
    }

    public function genereAllSlots(array $previsionnels)
    {
        foreach ($previsionnels as $previsionnel) {
            $this->getGroupes($previsionnel);
            $this->genereSlots($previsionnel);
        }
        $this->entityManager->flush();

        return $this->nbSlots;
    }

    private function genereSlots(Previsionnel $previsionnel)
    {
        if ($previsionnel->getProgression() !== null) {
            $progression = $previsionnel->getProgression();
            foreach ($progression->getProgression() as $semaine => $value) {
                $this->genereSlotsFromProgression($value, $semaine, $previsionnel);
            }
        }
    }

    private function genereSlotsFromProgression(string $value, int|string $semaine, Previsionnel $previsionnel)
    {
        $creneaux = explode(' ', $value);
        foreach ($creneaux as $creneau) {
            $typeCours = substr($creneau, 0, 2);
            $numeroSeance = substr($creneau, 2);
            $nbGroupes = match ($typeCours) {
                'TD' => explode(' ', $previsionnel->getProgression()?->getGrTd()),
                'TP' => explode(' ', $previsionnel->getProgression()?->getGrTp()),
                'CM' => ['CM'],
                default => 0,
            };

            foreach($nbGroupes as $getGr) {
                $this->createEdtEvent($previsionnel, $typeCours, $semaine, $numeroSeance, $getGr);
            }

        }
    }

    private function createEdtEvent(Previsionnel $previsionnel, string $typeCours, int|string $semaine, string $numeroSeance, string $getGr)
    {
        $edtEvent = new EdtEvent();
        $semestre = $previsionnel->getSemestre();
        $edtEvent->setPersonnel($previsionnel->getPersonnel());
        $edtEvent->setLibPersonnel($previsionnel->getPersonnel()?->getDisplay());
        $edtEvent->setCodePersonnel($previsionnel->getPersonnel()?->getNumeroHarpege());
        $edtEvent->setSemestre($semestre);
        $edtEvent->setAnneeUniversitaire($previsionnel->getAnneeUniversitaire());
        $edtEvent->setType($typeCours);
        $edtEvent->setSemaineFormation($semaine);
        $edtEvent->setEnseignement($previsionnel->getMatiere());
        $edtEvent->setLibModule($previsionnel->getMatiere()?->getLibelle());
        $edtEvent->setCodeModule($previsionnel->getMatiere()?->getCodeApogee());
        $edtEvent->setOrdreSeance($numeroSeance);
        $edtEvent->setCouleur($semestre?->getAnnee()?->getCouleur());
        $groupe = $this->groupes[$previsionnel->getSemestre()?->getId()][strtoupper($getGr)];
        $edtEvent->setGroupe($groupe);
        $edtEvent->setLibGroupe($groupe->getLibelle());
        $edtEvent->setCodeGroupe($groupe->getCodeApogee());

        $this->entityManager->persist($edtEvent);
        $this->nbSlots++;
    }

    private function getGroupes(Previsionnel $previsionnel)
    {
        $semestre = $previsionnel->getSemestre();
        if ($semestre !== null && !array_key_exists($semestre->getId(), $this->groupes)) {
            $groupes = $semestre->getStructureGroupes();
            foreach ($groupes as $groupe) {
                $this->groupes[$semestre->getId()][$groupe->getLibelle()] = $groupe;
            }
        }
    }
}
