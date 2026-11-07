<?php

namespace UnifolioBundle\Service\Suivi;

use App\Entity\Users\Etudiant;
use UnifolioBundle\Service\Campagne\CampagneNormalizer;
use UnifolioBundle\Service\Campagne\CampagneResolver;
use UnifolioBundle\Service\Perimetre\UnifolioPerimetre;
use UnifolioBundle\Service\Trace\TraceNormalizer;

/** Progression d'un étudiant sur l'ensemble de son cursus : une entrée par année de BUT, plus les échéances à venir. */
class SuiviEtudiant
{
    public function __construct(
        private readonly SuiviCalculator $calculateur,
        private readonly CampagneResolver $campagnes,
        private readonly CampagneNormalizer $campagneNormalizer,
        private readonly UnifolioPerimetre $perimetre,
        private readonly TraceNormalizer $traces,
    ) {
    }

    /** @return array{annees: list<array<string, mixed>>, echeances: list<array<string, mixed>>} */
    public function pour(Etudiant $etudiant): array
    {
        $scolarites = array_filter($etudiant->getScolarites()->toArray(), fn ($s) => $this->perimetre->isScolariteEligible($s));
        usort($scolarites, static fn ($a, $b) => strcmp((string) $a->getAnneeUniversitaire()?->getLibelle(), (string) $b->getAnneeUniversitaire()?->getLibelle()));

        $annees = [];
        $echeances = [];
        $maintenant = new \DateTimeImmutable();
        foreach ($scolarites as $scolarite) {
            $campagne = $this->campagnes->pourScolarite($scolarite);
            $annees[] = ['scolarite' => $this->traces->scolarite($scolarite), 'campagne' => $campagne !== null ? $this->campagneNormalizer->resume($campagne) : null]
                + $this->calculateur->pourScolarite($scolarite, false, true);

            if ($campagne !== null && !$campagne->isCloturee($maintenant)) {
                foreach (['depotLimite' => ['Dépôt des traces', $campagne->getDepotLimite()], 'evaluationLimite' => ['Fin des évaluations', $campagne->getEvaluationLimite()]] as $cle => [$libelle, $date]) {
                    if ($date !== null && $date >= $maintenant) {
                        $echeances[] = ['type' => $cle, 'libelle' => $libelle, 'campagne' => $campagne->getLibelle(), 'date' => $date->format(\DATE_ATOM)];
                    }
                }
            }
        }
        usort($echeances, static fn (array $a, array $b) => strcmp($a['date'], $b['date']));

        return ['annees' => $annees, 'echeances' => $echeances];
    }
}
