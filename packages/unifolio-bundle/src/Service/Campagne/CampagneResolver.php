<?php

namespace UnifolioBundle\Service\Campagne;

use App\Entity\Etudiant\EtudiantScolarite;
use UnifolioBundle\Entity\Campagne;
use UnifolioBundle\Repository\CampagneRepository;
use UnifolioBundle\Service\Perimetre\UnifolioPerimetre;

class CampagneResolver
{
    public function __construct(
        private readonly CampagneRepository $campagnes,
        private readonly UnifolioPerimetre $perimetre,
    ) {
    }

    /**
     * Campagne qui encadre une année de BUT d'un étudiant : celle visant précisément son année de BUT,
     * à défaut celle de tout le diplôme.
     */
    public function pourScolarite(EtudiantScolarite $scolarite): ?Campagne
    {
        $diplome = $this->perimetre->getDiplome($scolarite);
        $annee = $scolarite->getAnneeUniversitaire();
        if ($diplome === null || $annee === null) {
            return null;
        }
        $anneeFormation = $this->perimetre->getAnneeFormation($scolarite);

        $generale = null;
        foreach ($this->campagnes->findPour($diplome, $annee) as $campagne) {
            $visee = $campagne->getAnneeFormation();
            if ($visee === null) {
                $generale ??= $campagne;
            } elseif ($anneeFormation !== null && $visee->getId() === $anneeFormation->getId()) {
                return $campagne;
            }
        }

        return $generale;
    }
}
