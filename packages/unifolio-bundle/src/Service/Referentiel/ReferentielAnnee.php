<?php

namespace UnifolioBundle\Service\Referentiel;

use App\Entity\Apc\ApcApprentissageCritique;
use App\Entity\Apc\ApcCompetence;
use App\Entity\Apc\ApcNiveau;
use App\Entity\Apc\ApcReferentiel;
use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Structure\StructureAnnee;
use UnifolioBundle\Service\Perimetre\UnifolioPerimetre;

/**
 * Éléments du référentiel APC accessibles pour une année de BUT donnée (D5) :
 * niveaux de l'année de formation, filtrés par le parcours du diplôme,
 * leurs compétences et leurs apprentissages critiques.
 *
 * Dans les données actuelles, apc_niveau.annee_id n'est pas renseigné : le rang
 * d'année du niveau (ordreAnnee) dans le référentiel du diplôme fait alors foi.
 */
class ReferentielAnnee
{
    public function __construct(
        private readonly UnifolioPerimetre $perimetre,
    ) {
    }

    /** @return ApcNiveau[] triés par compétence puis ordre */
    public function niveaux(EtudiantScolarite $scolarite): array
    {
        $annee = $this->perimetre->getAnneeFormation($scolarite);
        if ($annee === null) {
            return [];
        }
        $diplome = $this->perimetre->getDiplome($scolarite);
        $parcours = $diplome?->getParcours();

        $niveaux = [];
        foreach ($this->niveauxCandidats($annee, $diplome?->getReferentiel()) as $niveau) {
            $parcoursNiveau = $niveau->getParcours();
            // Un niveau sans parcours est commun à tous les parcours.
            if ($parcours === null || $parcoursNiveau->isEmpty() || $parcoursNiveau->contains($parcours)) {
                $niveaux[] = $niveau;
            }
        }

        usort($niveaux, static fn (ApcNiveau $a, ApcNiveau $b) => [$a->getCompetence()?->getId(), $a->getOrdre()] <=> [$b->getCompetence()?->getId(), $b->getOrdre()]);

        return $niveaux;
    }

    /**
     * Lien explicite niveau → année (apc_niveau.annee_id) s'il est renseigné ; sinon, niveaux du
     * référentiel du diplôme dont le rang d'année (ordreAnnee, à défaut ordre) correspond à l'année de BUT.
     *
     * @return iterable<ApcNiveau>
     */
    private function niveauxCandidats(StructureAnnee $annee, ?ApcReferentiel $referentiel): iterable
    {
        if (!$annee->getNiveaux()->isEmpty()) {
            return $annee->getNiveaux();
        }
        if ($referentiel === null) {
            return [];
        }

        $rang = $annee->getOrdre();
        $niveaux = [];
        foreach ($referentiel->getCompetences() as $competence) {
            foreach ($competence->getNiveaux() as $niveau) {
                $rangNiveau = ($niveau->getOrdreAnnee() ?? 0) > 0 ? $niveau->getOrdreAnnee() : $niveau->getOrdre();
                if ($rangNiveau === $rang) {
                    $niveaux[] = $niveau;
                }
            }
        }

        return $niveaux;
    }

    public function contient(EtudiantScolarite $scolarite, ApcCompetence|ApcNiveau|ApcApprentissageCritique $cible): bool
    {
        foreach ($this->niveaux($scolarite) as $niveau) {
            $ok = match (true) {
                $cible instanceof ApcNiveau => $this->meme($niveau, $cible),
                $cible instanceof ApcCompetence => $this->meme($niveau->getCompetence(), $cible),
                $cible instanceof ApcApprentissageCritique => $this->meme($cible->getNiveau(), $niveau),
            };
            if ($ok) {
                return true;
            }
        }

        return false;
    }

    /**
     * Arbre compétence → niveaux → AC pour le sélecteur de rattachement.
     *
     * @return list<array<string, mixed>>
     */
    public function arbre(EtudiantScolarite $scolarite): array
    {
        $competences = [];
        foreach ($this->niveaux($scolarite) as $niveau) {
            $competence = $niveau->getCompetence();
            if ($competence === null) {
                continue;
            }
            $cle = $competence->getId() ?? spl_object_id($competence);
            $competences[$cle] ??= [
                'id' => $competence->getId(),
                'libelle' => $competence->getLibelle(),
                'nomCourt' => $competence->getNomCourt(),
                'couleur' => $competence->getCouleur(),
                'niveaux' => [],
            ];

            $acs = [];
            foreach ($niveau->getApprentissageCritique() as $ac) {
                $acs[] = ['id' => $ac->getId(), 'code' => $ac->getCode(), 'libelle' => $ac->getLibelle()];
            }
            usort($acs, static fn (array $a, array $b) => strnatcmp((string) $a['code'], (string) $b['code']));

            $competences[$cle]['niveaux'][] = [
                'id' => $niveau->getId(),
                'libelle' => $niveau->getLibelle(),
                'ordre' => $niveau->getOrdre(),
                'apprentissagesCritiques' => $acs,
            ];
        }

        return array_values($competences);
    }

    private function meme(?object $a, ?object $b): bool
    {
        if ($a === null || $b === null) {
            return false;
        }

        return $a === $b || ($a->getId() !== null && $a->getId() === $b->getId());
    }
}
