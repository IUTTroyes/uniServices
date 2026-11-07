<?php

namespace UnifolioBundle\Services\Dashboard\Provider;

use App\Domain\Dashboard\DashboardContext;
use App\Domain\Dashboard\WidgetDataProviderInterface;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Security\UserEffectivePermissionService;
use UnifolioBundle\Entity\Campagne;
use UnifolioBundle\Repository\CampagneAffectationRepository;
use UnifolioBundle\Repository\CampagneRepository;
use UnifolioBundle\Security\ReferentContexte;
use UnifolioBundle\Security\UnifolioRoles;
use UnifolioBundle\Service\Campagne\CampagneEtudiants;
use UnifolioBundle\Service\Evaluation\AutoEvaluationManager;
use UnifolioBundle\Service\Evaluation\BilanAnnee;
use UnifolioBundle\Service\Evaluation\FileEvaluation;
use UnifolioBundle\Service\Perimetre\UnifolioPerimetre;
use UnifolioBundle\Service\Trace\UnifolioValidationException;

class PortfolioWidgetDataProvider implements WidgetDataProviderInterface
{
    private const FENETRE_ALERTE = '+7 days';

    public function __construct(
        private readonly FileEvaluation $file,
        private readonly CampagneRepository $campagnes,
        private readonly CampagneAffectationRepository $affectations,
        private readonly CampagneEtudiants $etudiants,
        private readonly UnifolioPerimetre $perimetre,
        private readonly BilanAnnee $bilan,
        private readonly AutoEvaluationManager $autoEvaluations,
        private readonly UserEffectivePermissionService $permissions,
        private readonly ReferentContexte $contexte,
    ) {
    }

    public function supports(string $code): bool
    {
        return str_starts_with($code, 'portfolio.');
    }

    public function getData(string $code, Personnel|Etudiant $user, ?DashboardContext $context = null): array
    {
        return match ($code) {
            'portfolio.to_correct' => $user instanceof Personnel ? $this->aCorriger($user) : ['items' => []],
            'portfolio.progress' => $user instanceof Etudiant ? $this->progression($user) : [],
            'portfolio.alerts' => $user instanceof Personnel ? $this->alertes($user) : ['items' => []],
            default => [],
        };
    }

    /** @return array{items: list<string>, total: int} */
    private function aCorriger(Personnel $user): array
    {
        $items = [];
        $total = 0;
        foreach ($this->file->pour($user) as $groupe) {
            if ($groupe['campagne']['phase']['value'] === 'cloturee') {
                continue;
            }
            $t = $groupe['totaux'];
            $parts = array_filter([
                $t['aEvaluer'] ? sprintf('%d à évaluer', $t['aEvaluer']) : null,
                $t['enCours'] ? sprintf('%d en cours', $t['enCours']) : null,
                $t['aReprendre'] ? sprintf('%d à reprendre', $t['aReprendre']) : null,
            ]);
            if ($parts === []) {
                continue;
            }
            $total += $t['aEvaluer'] + $t['enCours'] + $t['aReprendre'];
            $items[] = sprintf('%s — %s', $groupe['campagne']['libelle'], implode(', ', $parts));
            $eleves = array_filter($groupe['etudiants'], static fn (array $e) => $e['aEvaluer'] + $e['aReprendre'] > 0);
            usort($eleves, static fn (array $a, array $b) => ($b['aEvaluer'] + $b['aReprendre']) <=> ($a['aEvaluer'] + $a['aReprendre']));
            foreach (\array_slice($eleves, 0, 3) as $e) {
                $items[] = sprintf('• %s %s : %d', $e['nom'], $e['prenom'], $e['aEvaluer'] + $e['aReprendre']);
            }
        }

        return ['items' => $items, 'total' => $total];
    }

    /** @return array{validated: int, target: int, label: string} */
    private function progression(Etudiant $etudiant): array
    {
        $vide = ['validated' => 0, 'target' => 0, 'label' => 'éléments couverts par une trace déposée'];
        $scolarite = $this->perimetre->getScolariteActive($etudiant);
        if ($scolarite === null || !$this->perimetre->isScolariteEligible($scolarite)) {
            return $vide;
        }
        try {
            $this->autoEvaluations->versionPour($scolarite);
            $granularite = $this->autoEvaluations->granularitePour($scolarite);
        } catch (UnifolioValidationException) {
            return $vide;
        }

        $elements = $this->bilan->elements($etudiant, $scolarite, $granularite);
        $couverts = array_filter($elements, static fn (array $el) => array_filter(
            $el['traces'],
            static fn (array $t) => \in_array($t['statutValue'], ['deposee', 'en_evaluation', 'evaluee'], true)
        ) !== []);

        return ['validated' => \count($couverts), 'target' => \count($elements), 'label' => 'éléments couverts par une trace déposée'];
    }

    /** @return array{items: list<string>} */
    private function alertes(Personnel $user): array
    {
        $campagnes = [];
        foreach ($this->file->pour($user) as $groupe) {
            $c = $this->campagnes->findOneByUuid($groupe['campagne']['uuid']);
            if ($c !== null && !$c->isCloturee()) {
                $campagnes[$c->getId()] = [$c, $groupe['totaux']['aEvaluer'] + $groupe['totaux']['enCours'] + $groupe['totaux']['aReprendre']];
            }
        }
        $estReferent = $this->contexte->isSuperAdmin($user) || $this->permissions->hasAnyPermission($user, [UnifolioRoles::REFERENT]);
        $geres = [];
        if ($estReferent) {
            foreach ($this->campagnes->findOuvertes() as $c) {
                if ($this->contexte->administre($user, $c->getDiplome()->getDepartement())) {
                    $geres[$c->getId()] = $c;
                }
            }
        }

        $items = [];
        $bientot = new \DateTimeImmutable(self::FENETRE_ALERTE);
        foreach ($campagnes as [$c, $enAttente]) {
            if ($enAttente === 0) {
                continue;
            }
            $items[] = $c->isEvaluationEnRetard()
                ? sprintf('%s : limite d\'évaluation dépassée (%d en attente)', $c->getLibelle(), $enAttente)
                : ($c->getEvaluationLimite() !== null && $c->getEvaluationLimite() <= $bientot
                    ? sprintf('%s : évaluation à rendre avant le %s (%d en attente)', $c->getLibelle(), $c->getEvaluationLimite()->format('d/m'), $enAttente)
                    : null);
        }
        foreach ($geres as $c) {
            $items[] = $this->alerteReferent($c, $bientot);
        }

        return ['items' => array_values(array_filter($items))];
    }

    private function alerteReferent(Campagne $c, \DateTimeImmutable $bientot): ?string
    {
        $affectes = [];
        foreach ($this->affectations->findParCampagne($c) as $a) {
            $affectes[$a->getEtudiant()->getId()] = true;
        }
        $sansEvaluateur = \count(array_diff_key($this->etudiants->scolarites($c), $affectes));
        if ($sansEvaluateur > 0) {
            return sprintf('%s : %d étudiant(s) sans évaluateur', $c->getLibelle(), $sansEvaluateur);
        }
        if ($c->getDepotLimite() !== null && $c->getDepotLimite() > new \DateTimeImmutable() && $c->getDepotLimite() <= $bientot) {
            return sprintf('%s : fin du dépôt le %s', $c->getLibelle(), $c->getDepotLimite()->format('d/m'));
        }

        return null;
    }
}
