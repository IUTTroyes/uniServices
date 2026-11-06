<?php

namespace UnifolioBundle\Service\Evaluation;

use App\Entity\Apc\ApcApprentissageCritique;
use App\Entity\Apc\ApcCompetence;
use App\Entity\Apc\ApcNiveau;
use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Users\Etudiant;
use UnifolioBundle\Entity\AutoEvaluation;
use UnifolioBundle\Entity\TraceRattachement;
use UnifolioBundle\Enum\GranulariteEnum;
use UnifolioBundle\Repository\AutoEvaluationRepository;
use UnifolioBundle\Repository\TraceRepository;
use UnifolioBundle\Service\Referentiel\ReferentielAnnee;

/**
 * Vue d'ensemble de l'auto-évaluation d'une année : pour chaque élément du référentiel
 * (selon la granularité de la formation), les traces qui le démontrent, leurs auto-évaluations
 * et le bilan de l'étudiant.
 */
class BilanAnnee
{
    public function __construct(
        private readonly ReferentielAnnee $referentiel,
        private readonly TraceRepository $traces,
        private readonly AutoEvaluationRepository $autoEvaluations,
        private readonly AutoEvaluationNormalizer $normalizer,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function elements(Etudiant $etudiant, EtudiantScolarite $scolarite, GranulariteEnum $granularite): array
    {
        $rattachements = [];
        foreach ($this->traces->findBibliotheque($etudiant, ['scolarite' => $scolarite->getId()]) as $trace) {
            foreach ($trace->getRattachements() as $r) {
                $rattachements[] = $r;
            }
        }
        $autoParRattachement = $this->autoEvaluations->findParRattachements($rattachements);

        $bilans = [];
        foreach ($this->autoEvaluations->findParScolarite($scolarite) as $a) {
            if ($a->isBilan() && $a->getGranularite() === $granularite) {
                $bilans[$a->getCible()->getId()] = $a;
            }
        }

        $elements = [];
        foreach ($this->cibles($scolarite, $granularite) as $cible) {
            $competence = $cible instanceof ApcCompetence ? $cible
                : ($cible instanceof ApcNiveau ? $cible->getCompetence() : $cible->getNiveau()?->getCompetence());

            $traces = [];
            foreach ($rattachements as $r) {
                if ($this->concerne($r, $cible)) {
                    $auto = $autoParRattachement[$r->getId()] ?? null;
                    $traces[] = [
                        'uuid' => $r->getTrace()->getUuidString(),
                        'titre' => $r->getTrace()->getTitre(),
                        'statut' => $r->getTrace()->getStatut()->getLibelle(),
                        'rattachementId' => $r->getId(),
                        'autoEvaluation' => $auto !== null ? $this->normalizer->resume($auto) : null,
                    ];
                }
            }

            $bilan = $bilans[$cible->getId()] ?? null;
            $elements[] = [
                'type' => $granularite->value,
                'id' => $cible->getId(),
                'code' => $cible instanceof ApcApprentissageCritique ? $cible->getCode() : null,
                'ordre' => $cible instanceof ApcNiveau ? $cible->getOrdre() : null,
                'libelle' => $cible->getLibelle(),
                'competence' => $competence !== null ? ['id' => $competence->getId(), 'nomCourt' => $competence->getNomCourt() ?? $competence->getLibelle(), 'libelle' => $competence->getLibelle(), 'couleur' => $competence->getCouleur()] : null,
                'traces' => $traces,
                'bilan' => $bilan !== null ? $this->normalizer->resume($bilan) : null,
            ];
        }

        return $elements;
    }

    /** @return list<ApcCompetence|ApcNiveau|ApcApprentissageCritique> */
    private function cibles(EtudiantScolarite $scolarite, GranulariteEnum $granularite): array
    {
        $niveaux = $this->referentiel->niveaux($scolarite);

        return match ($granularite) {
            GranulariteEnum::NIVEAU => $niveaux,
            GranulariteEnum::AC => array_merge([], ...array_map(static fn (ApcNiveau $n) => $n->getApprentissageCritique()->toArray(), $niveaux)),
            GranulariteEnum::COMPETENCE => $this->competencesDe($niveaux),
        };
    }

    /**
     * @param ApcNiveau[] $niveaux
     *
     * @return ApcCompetence[] sans doublon, dans l'ordre des niveaux
     */
    private function competencesDe(array $niveaux): array
    {
        $competences = [];
        foreach ($niveaux as $niveau) {
            $c = $niveau->getCompetence();
            if ($c !== null) {
                $competences[$c->getId() ?? spl_object_id($c)] = $c;
            }
        }

        return array_values($competences);
    }

    /** Un rattachement concerne un élément s'il le vise directement ou vise un élément plus fin qui en dépend. */
    private function concerne(TraceRattachement $r, ApcCompetence|ApcNiveau|ApcApprentissageCritique $cible): bool
    {
        $meme = static fn (?object $a, ?object $b) => $a !== null && $b !== null && $a->getId() === $b->getId();

        return match (true) {
            $cible instanceof ApcApprentissageCritique => $meme($r->getApprentissageCritique(), $cible),
            $cible instanceof ApcNiveau => $meme($r->getNiveau(), $cible) || $meme($r->getApprentissageCritique()?->getNiveau(), $cible),
            $cible instanceof ApcCompetence => $meme($r->getCompetenceParente(), $cible),
        };
    }
}
