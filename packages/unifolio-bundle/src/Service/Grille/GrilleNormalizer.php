<?php

namespace UnifolioBundle\Service\Grille;

use UnifolioBundle\Entity\Grille;
use UnifolioBundle\Entity\GrilleCritere;
use UnifolioBundle\Entity\GrilleEchelon;
use UnifolioBundle\Entity\GrilleVersion;
use UnifolioBundle\Entity\ParametrageFormation;

class GrilleNormalizer
{
    /** @return array<string, mixed> */
    public function resume(Grille $grille, bool $modifiable = false): array
    {
        $publiee = $grille->getVersionPubliee();
        $brouillon = $grille->getBrouillon();

        return [
            'uuid' => $grille->getUuidString(),
            'libelle' => $grille->getLibelle(),
            'description' => $grille->getDescription(),
            'granularite' => ['value' => $grille->getGranularite()->value, 'libelle' => $grille->getGranularite()->getLibelle()],
            'portee' => $grille->getPortee(),
            'libellePortee' => match ($grille->getPortee()) {
                Grille::PORTEE_DIPLOME => $grille->getDiplome()?->getLibelle() ?? 'Diplôme',
                Grille::PORTEE_DEPARTEMENT => $grille->getDepartement()?->getLibelle() ?? 'Département',
                default => 'Toutes les formations ' . $grille->getTypeDiplome()->getSigle(),
            },
            'diplome' => $grille->getDiplome() !== null ? ['id' => $grille->getDiplome()->getId(), 'libelle' => $grille->getDiplome()->getLibelle()] : null,
            'actif' => $grille->isActif(),
            'versionPubliee' => $publiee?->getNumero(),
            'publieeLe' => $publiee?->getPublieeLe()?->format(\DATE_ATOM),
            'brouillon' => $brouillon?->getNumero(),
            'modifiable' => $modifiable,
        ];
    }

    /** @return array<string, mixed> */
    public function detail(Grille $grille, bool $modifiable = false): array
    {
        return $this->resume($grille, $modifiable) + [
            'versions' => array_map(fn (GrilleVersion $v) => [
                'numero' => $v->getNumero(),
                'publieeLe' => $v->getPublieeLe()?->format(\DATE_ATOM),
            ], $grille->getVersions()->toArray()),
            'contenuPublie' => $grille->getVersionPubliee() !== null ? $this->contenu($grille->getVersionPubliee()) : null,
            'contenuBrouillon' => $grille->getBrouillon() !== null ? $this->contenu($grille->getBrouillon()) : null,
        ];
    }

    /** @return array<string, mixed> */
    public function contenu(GrilleVersion $version): array
    {
        return [
            'numero' => $version->getNumero(),
            'publiee' => $version->isPubliee(),
            'erreursPublication' => $version->isPubliee() ? [] : $version->getErreursPublication(),
            'echelons' => array_map(static fn (GrilleEchelon $e) => [
                'id' => $e->getId(),
                'libelle' => $e->getLibelle(),
                'valeur' => $e->getValeur(),
                'couleur' => $e->getCouleur(),
                'descripteur' => $e->getDescripteur(),
            ], $version->getEchelons()->toArray()),
            'criteres' => array_map(static fn (GrilleCritere $c) => [
                'id' => $c->getId(),
                'libelle' => $c->getLibelle(),
                'description' => $c->getDescription(),
                'aide' => $c->getAide(),
                'type' => $c->getType()->value,
                'source' => $c->getSource()->value,
                'poids' => $c->getPoids(),
                'noteMax' => $c->getNoteMax(),
                'obligatoire' => $c->isObligatoire(),
                'visibleEtudiant' => $c->isVisibleEtudiant(),
                'utiliseAutoEval' => $c->isUtiliseAutoEval(),
            ], $version->getCriteres()->toArray()),
        ];
    }

    /** @return array<string, mixed>|null */
    public function parametrage(?ParametrageFormation $p): ?array
    {
        if ($p === null) {
            return null;
        }

        return [
            'id' => $p->getId(),
            'granularite' => $p->getGranularite()->value,
            'agregation' => $p->getAgregation()->value,
            'grille' => $p->getGrille()?->getUuidString(),
            'grilleLibelle' => $p->getGrille()?->getLibelle(),
            'applicableDepuis' => ['id' => $p->getApplicableDepuis()->getId(), 'libelle' => $p->getApplicableDepuis()->getLibelle()],
        ];
    }
}
