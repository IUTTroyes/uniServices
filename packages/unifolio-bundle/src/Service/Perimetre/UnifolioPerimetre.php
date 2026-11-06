<?php

namespace UnifolioBundle\Service\Perimetre;

use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Structure\StructureAnnee;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Users\Etudiant;
use App\Repository\EtudiantScolariteRepository;

/**
 * Périmètre d'UniFolio (décision D1) : seuls les étudiants inscrits dans un
 * diplôme dont le type est APC (BUT) ont accès à l'espace portfolio.
 */
class UnifolioPerimetre
{
    public function __construct(
        private readonly EtudiantScolariteRepository $scolariteRepository,
    ) {
    }

    public function getScolariteActive(Etudiant $etudiant): ?EtudiantScolarite
    {
        return $this->scolariteRepository->findActiveByEtudiant($etudiant);
    }

    public function isEtudiantEligible(Etudiant $etudiant): bool
    {
        $scolarite = $this->getScolariteActive($etudiant);

        return $scolarite !== null && $this->isScolariteEligible($scolarite);
    }

    public function isScolariteEligible(EtudiantScolarite $scolarite): bool
    {
        return $this->isDiplomeEligible($this->getDiplome($scolarite));
    }

    public function isDiplomeEligible(?StructureDiplome $diplome): bool
    {
        return $diplome?->getTypeDiplome()?->isApc() === true;
    }

    /** Diplôme de la scolarité, déduit de ses semestres. */
    public function getDiplome(EtudiantScolarite $scolarite): ?StructureDiplome
    {
        foreach ($scolarite->getScolariteSemestre() as $scolariteSemestre) {
            $diplome = $scolariteSemestre->getSemestre()?->getDiplome();
            if ($diplome !== null) {
                return $diplome;
            }
        }

        return null;
    }

    /** Année de formation (BUT1, BUT2, BUT3) : sert à limiter le référentiel à l'année concernée. */
    public function getAnneeFormation(EtudiantScolarite $scolarite): ?StructureAnnee
    {
        return $scolarite->getAnnee()[0] ?? null;
    }
}
