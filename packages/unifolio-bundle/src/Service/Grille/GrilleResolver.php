<?php

namespace UnifolioBundle\Service\Grille;

use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDiplome;
use UnifolioBundle\Entity\Grille;
use UnifolioBundle\Entity\ParametrageFormation;
use UnifolioBundle\Enum\GranulariteEnum;
use UnifolioBundle\Repository\GrilleRepository;
use UnifolioBundle\Repository\ParametrageFormationRepository;
use UnifolioBundle\Service\Perimetre\UnifolioPerimetre;

/**
 * Règles applicables à une formation pour une année universitaire :
 * paramétrage en vigueur, granularité, grille à utiliser.
 */
class GrilleResolver
{
    public function __construct(
        private readonly ParametrageFormationRepository $parametrages,
        private readonly GrilleRepository $grilles,
        private readonly UnifolioPerimetre $perimetre,
    ) {
    }

    public function parametrage(StructureDiplome $diplome, StructureAnneeUniversitaire $annee): ?ParametrageFormation
    {
        return $this->parametrages->findApplicable($diplome, $annee);
    }

    /** Granularité imposée par la formation ; null si elle n'a pas encore été paramétrée (tout est permis). */
    public function granularite(StructureDiplome $diplome, StructureAnneeUniversitaire $annee): ?GranulariteEnum
    {
        return $this->parametrage($diplome, $annee)?->getGranularite();
    }

    public function granularitePourScolarite(EtudiantScolarite $scolarite): ?GranulariteEnum
    {
        [$diplome, $annee] = $this->contexte($scolarite);

        return $diplome !== null && $annee !== null ? $this->granularite($diplome, $annee) : null;
    }

    /**
     * Grille à utiliser : celle choisie dans le paramétrage si elle est publiée, sinon la plus
     * spécifique des grilles actives et publiées (diplôme > département > type de diplôme)
     * correspondant à la granularité de la formation.
     */
    public function grille(StructureDiplome $diplome, StructureAnneeUniversitaire $annee): ?Grille
    {
        $parametrage = $this->parametrage($diplome, $annee);
        $choisie = $parametrage?->getGrille();
        if ($choisie !== null && $choisie->isActif() && $choisie->hasVersionPubliee()) {
            return $choisie;
        }

        foreach ($this->grilles->findApplicables($diplome, $parametrage?->getGranularite()) as $grille) {
            if ($grille->hasVersionPubliee()) {
                return $grille;
            }
        }

        return null;
    }

    public function grillePourScolarite(EtudiantScolarite $scolarite): ?Grille
    {
        [$diplome, $annee] = $this->contexte($scolarite);

        return $diplome !== null && $annee !== null ? $this->grille($diplome, $annee) : null;
    }

    /** @return array{0: ?StructureDiplome, 1: ?StructureAnneeUniversitaire} */
    private function contexte(EtudiantScolarite $scolarite): array
    {
        return [$this->perimetre->getDiplome($scolarite), $scolarite->getAnneeUniversitaire()];
    }
}
