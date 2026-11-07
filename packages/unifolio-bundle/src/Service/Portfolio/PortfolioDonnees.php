<?php

namespace UnifolioBundle\Service\Portfolio;

use App\Entity\Apc\ApcCompetence;
use App\Entity\Apc\ApcNiveau;
use UnifolioBundle\Entity\Portfolio;
use UnifolioBundle\Entity\Trace;
use UnifolioBundle\Enum\StatutTraceEnum;
use UnifolioBundle\Repository\CvRepository;
use UnifolioBundle\Repository\TraceRepository;
use UnifolioBundle\Service\Perimetre\UnifolioPerimetre;
use UnifolioBundle\Service\Referentiel\ReferentielAnnee;
use UnifolioBundle\Service\Trace\TraceNormalizer;

/**
 * Données nécessaires au rendu des blocs « métier » d'un portfolio (traces, progression, CV, parcours).
 * Réservées au propriétaire : les liens de fichiers pointent vers l'API authentifiée. Le rendu public
 * (lot 6) utilisera un instantané de ces données, jamais un accès direct aux médias privés.
 */
class PortfolioDonnees
{
    private const TRACES_MAX = 200;
    private const MEDIAS_PAR_TRACE = 12;

    public function __construct(
        private readonly TraceRepository $traces,
        private readonly TraceNormalizer $traceNormalizer,
        private readonly ReferentielAnnee $referentiel,
        private readonly CvRepository $cvs,
        private readonly CvNormalizer $cvNormalizer,
        private readonly UnifolioPerimetre $perimetre,
    ) {
    }

    /** @return array<string, mixed> */
    public function pour(Portfolio $portfolio): array
    {
        $etudiant = $portfolio->getEtudiant();
        $traces = \array_slice($this->traces->findBibliotheque($etudiant), 0, self::TRACES_MAX);
        $cv = $this->cvs->findOneByEtudiant($etudiant);

        return [
            'traces' => array_map($this->trace(...), $traces),
            'progression' => $portfolio->getScolarite() !== null ? $this->progression($portfolio, $traces) : [],
            'cv' => $cv !== null ? $this->cvNormalizer->document($cv) : $this->cvNormalizer->vide(),
            'parcours' => $this->parcours($portfolio),
        ];
    }

    /** @return array<string, mixed> */
    private function trace(Trace $trace): array
    {
        return $this->traceNormalizer->resume($trace) + [
            'contexte' => $trace->getContexte() !== null ? mb_strimwidth($trace->getContexte(), 0, 400, '…') : null,
            'medias' => array_map(
                fn ($m) => $this->traceNormalizer->media($trace, $m),
                \array_slice($trace->getMedias()->toArray(), 0, self::MEDIAS_PAR_TRACE)
            ),
        ];
    }

    /**
     * Par compétence de l'année : nombre de niveaux et nombre de niveaux couverts par au moins une trace déposée.
     * Un rattachement à une compétence couvre tous ses niveaux de l'année ; à un AC, le niveau de cet AC.
     *
     * @param Trace[] $traces
     *
     * @return list<array<string, mixed>>
     */
    private function progression(Portfolio $portfolio, array $traces): array
    {
        $niveaux = $this->referentiel->niveaux($portfolio->getScolarite());
        $couverts = [];
        foreach ($traces as $trace) {
            if (!\in_array($trace->getStatut(), [StatutTraceEnum::DEPOSEE, StatutTraceEnum::EN_EVALUATION, StatutTraceEnum::EVALUEE], true)) {
                continue;
            }
            foreach ($trace->getRattachements() as $r) {
                $cible = $r->getCible();
                foreach ($niveaux as $niveau) {
                    $touche = match (true) {
                        $cible instanceof ApcNiveau => $cible->getId() === $niveau->getId(),
                        $cible instanceof ApcCompetence => $cible->getId() === $niveau->getCompetence()?->getId(),
                        default => $cible->getNiveau()?->getId() === $niveau->getId(),
                    };
                    if ($touche) {
                        $couverts[$niveau->getId()] = true;
                    }
                }
            }
        }

        $parCompetence = [];
        foreach ($niveaux as $niveau) {
            $c = $niveau->getCompetence();
            if ($c === null) {
                continue;
            }
            $parCompetence[$c->getId()] ??= [
                'competence' => ['id' => $c->getId(), 'nomCourt' => $c->getNomCourt() ?? $c->getLibelle(), 'libelle' => $c->getLibelle(), 'couleur' => $c->getCouleur()],
                'total' => 0,
                'couverts' => 0,
            ];
            ++$parCompetence[$c->getId()]['total'];
            $parCompetence[$c->getId()]['couverts'] += isset($couverts[$niveau->getId()]) ? 1 : 0;
        }

        return array_values($parCompetence);
    }

    /** @return list<array<string, mixed>> */
    private function parcours(Portfolio $portfolio): array
    {
        $scolarites = array_filter($portfolio->getEtudiant()->getScolarites()->toArray(), fn ($s) => $this->perimetre->isScolariteEligible($s));
        usort($scolarites, static fn ($a, $b) => $a->getOrdre() <=> $b->getOrdre());

        return array_map(fn ($s) => [
            'annee' => $this->perimetre->getAnneeFormation($s)?->getLibelle(),
            'anneeUniversitaire' => $s->getAnneeUniversitaire()?->getLibelle(),
            'diplome' => $this->perimetre->getDiplome($s)?->getLibelle(),
            'actif' => $s->isActif(),
        ], array_values($scolarites));
    }
}
