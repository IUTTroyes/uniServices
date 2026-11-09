<?php

namespace UnifolioBundle\Service\Evaluation;

use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Users\Personnel;
use Doctrine\ORM\EntityManagerInterface;
use UnifolioBundle\Entity\Evaluation;
use UnifolioBundle\Entity\Trace;
use UnifolioBundle\Enum\StatutEvaluationEnum;
use UnifolioBundle\Enum\StatutTraceEnum;
use UnifolioBundle\Repository\EvaluationRepository;
use UnifolioBundle\Service\Campagne\CampagneNormalizer;
use UnifolioBundle\Service\Campagne\CampagneResolver;
use UnifolioBundle\Service\Grille\GrilleNormalizer;
use UnifolioBundle\Service\Trace\UnifolioValidationException;

/**
 * Évaluation « en lot » : tous les points d'une trace (ses rattachements) ou de l'année d'un étudiant
 * (ses bilans) sont chargés, enregistrés, rendus ou rouverts en une seule opération.
 *
 * Une évaluation n'est créée que pour un point effectivement renseigné : afficher le formulaire
 * ne crée rien en base.
 */
class EvaluationLot
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EvaluationManager $manager,
        private readonly EvaluationRepository $evaluations,
        private readonly EvaluationNormalizer $normalizer,
        private readonly GrilleNormalizer $grilles,
        private readonly AutoEvaluationManager $autoEvaluations,
        private readonly BilanAnnee $bilan,
        private readonly CampagneResolver $campagnes,
        private readonly CampagneNormalizer $campagneNormalizer,
        private readonly EvaluationNotifier $notifier,
    ) {
    }

    // ---------- Portées ----------

    /** @return array{trace: ?Trace, scolarite: EtudiantScolarite, cibles: list<CibleLot>} */
    public function porteeTrace(Trace $trace): array
    {
        $scolarite = $trace->getScolarite() ?? throw UnifolioValidationException::un('Cette trace n\'est rattachée à aucune année de BUT.');

        return ['trace' => $trace, 'scolarite' => $scolarite, 'cibles' => array_map(CibleLot::pourRattachement(...), $trace->getRattachements()->toArray())];
    }

    /** @return array{trace: ?Trace, scolarite: EtudiantScolarite, cibles: list<CibleLot>} */
    public function porteeBilans(EtudiantScolarite $scolarite): array
    {
        $granularite = $this->autoEvaluations->granularitePour($scolarite);

        return [
            'trace' => null,
            'scolarite' => $scolarite,
            'cibles' => array_map(static fn ($c) => CibleLot::pourElement($granularite, $c), $this->bilan->cibles($scolarite, $granularite)),
        ];
    }

    // ---------- Lecture ----------

    /**
     * @param array{trace: ?Trace, scolarite: EtudiantScolarite, cibles: list<CibleLot>} $portee
     *
     * @return array<string, mixed>
     */
    public function charger(Personnel $evaluateur, array $portee): array
    {
        $scolarite = $portee['scolarite'];
        $courante = $this->autoEvaluations->versionPour($scolarite);
        $campagne = $this->campagnes->pourScolarite($scolarite);

        $grilles = [$courante->getNumero() => $this->contenuGrille($courante)];
        $items = [];
        foreach ($portee['cibles'] as $c) {
            $existante = $this->existante($evaluateur, $scolarite, $c);
            if ($existante !== null && !isset($grilles[$existante->getGrilleVersion()->getNumero()])) {
                $grilles[$existante->getGrilleVersion()->getNumero()] = $this->contenuGrille($existante->getGrilleVersion());
            }
            $competence = $c->competence();
            $items[] = [
                'cle' => $c->cle,
                'cible' => [
                    'type' => $c->granularite->value,
                    'id' => $c->cible->getId(),
                    'code' => $c->cible instanceof \App\Entity\Apc\ApcApprentissageCritique ? $c->cible->getCode() : null,
                    'ordre' => $c->cible instanceof \App\Entity\Apc\ApcNiveau ? $c->cible->getOrdre() : null,
                    'libelle' => $c->cible->getLibelle(),
                ],
                'competence' => $competence !== null ? ['id' => $competence->getId(), 'nomCourt' => $competence->getNomCourt() ?? $competence->getLibelle(), 'libelle' => $competence->getLibelle(), 'couleur' => $competence->getCouleur()] : null,
                'argumentaire' => $c->rattachement?->getArgumentaire(),
                'grilleVersion' => $existante?->getGrilleVersion()->getNumero() ?? $courante->getNumero(),
                'evaluation' => $existante !== null ? $this->normalizer->detailLeger($existante) : null,
                'autoEvaluation' => $this->normalizer->autoSoumise($c->rattachement, $scolarite, $c->granularite, (int) $c->cible->getId()),
                'autres' => array_map($this->normalizer->resume(...), $this->normalizer->autresRendues($c->rattachement, $scolarite, $c->granularite, (int) $c->cible->getId(), $evaluateur)),
            ];
        }

        $etudiant = $scolarite->getEtudiant();

        return [
            'mode' => $portee['trace'] !== null ? 'trace' : 'bilans',
            'campagne' => $campagne !== null ? $this->campagneNormalizer->resume($campagne) : null,
            'etudiant' => ['id' => $etudiant->getId(), 'nom' => $etudiant->getDisplay()],
            'peutEvaluer' => $campagne !== null && $campagne->getPhase()->accepteEvaluations(),
            'autoEvaluationRequise' => $campagne?->isAutoEvaluationRequise() ?? false,
            'versionCourante' => $courante->getNumero(),
            'grilles' => (object) $grilles,
            'items' => $items,
            'traceStatut' => $portee['trace'] !== null ? ['value' => $portee['trace']->getStatut()->value, 'libelle' => $portee['trace']->getStatut()->getLibelle()] : null,
        ];
    }

    // ---------- Écriture ----------

    /**
     * Enregistre les points transmis ; une évaluation est créée pour un point renseigné qui n'en a pas encore.
     *
     * @param array{trace: ?Trace, scolarite: EtudiantScolarite, cibles: list<CibleLot>} $portee
     * @param list<array{cle?: string, appreciation?: ?string, reponses?: mixed}>        $items
     */
    public function enregistrer(Personnel $evaluateur, array $portee, array $items): void
    {
        $parCle = [];
        foreach ($portee['cibles'] as $c) {
            $parCle[$c->cle] = $c;
        }

        $this->em->wrapInTransaction(function () use ($evaluateur, $portee, $items, $parCle): void {
            foreach ($items as $item) {
                $cle = (string) ($item['cle'] ?? '');
                $c = $parCle[$cle] ?? throw UnifolioValidationException::un('Un des points transmis ne fait pas partie de cette évaluation.');

                $existante = $this->existante($evaluateur, $portee['scolarite'], $c);
                $renseigne = $this->estRenseigne($item);
                if ($existante === null) {
                    if (!$renseigne) {
                        continue;
                    }
                    $existante = $this->ouvrir($evaluateur, $portee['scolarite'], $c);
                }
                if (!$existante->isModifiable()) {
                    if ($renseigne) {
                        throw UnifolioValidationException::un(sprintf('L\'évaluation de « %s » est rendue : rouvrez-la pour la modifier.', $c->libelle()));
                    }
                    continue;
                }
                $donnees = [];
                if (\array_key_exists('appreciation', $item)) {
                    $donnees['appreciation'] = $item['appreciation'];
                }
                if (\array_key_exists('reponses', $item)) {
                    $donnees['reponses'] = $item['reponses'];
                }
                $this->manager->enregistrer($existante, $donnees);
            }
        });
    }

    /**
     * Rend toutes les évaluations en cours et renseignées. Tout ou rien : si un point est incomplet,
     * rien n'est rendu et toutes les anomalies sont signalées.
     *
     * @param array{trace: ?Trace, scolarite: EtudiantScolarite, cibles: list<CibleLot>} $portee
     *
     * @return int nombre d'évaluations rendues
     */
    public function rendre(Personnel $evaluateur, array $portee, bool $visibleEtudiant = true): int
    {
        $aRendre = [];
        $erreurs = [];
        foreach ($portee['cibles'] as $c) {
            $e = $this->existante($evaluateur, $portee['scolarite'], $c);
            if ($e === null || $e->getStatut() !== StatutEvaluationEnum::EN_COURS) {
                continue;
            }
            if ($e->getReponses()->isEmpty() && $e->getAppreciation() === null) {
                continue; // point ouvert mais laissé vide : ignoré
            }
            foreach ($e->getErreursRendu() as $erreur) {
                $erreurs[] = sprintf('%s — %s', $c->libelle(), $erreur);
            }
            $aRendre[] = $e;
        }
        if ($aRendre === []) {
            throw UnifolioValidationException::un('Rien à rendre : renseignez au moins un point.');
        }
        if ($erreurs !== []) {
            throw new UnifolioValidationException($erreurs);
        }

        $this->em->wrapInTransaction(function () use ($aRendre, $visibleEtudiant): void {
            foreach ($aRendre as $e) {
                $this->manager->rendre($e, $visibleEtudiant, notifier: false);
            }
        });
        if ($visibleEtudiant) {
            $this->notifier->lotRendu($aRendre[0]->getEtudiant(), $aRendre[0]->getCampagne(), $evaluateur, $aRendre, $portee['trace']);
        }

        return \count($aRendre);
    }

    /**
     * Rouvre mes évaluations rendues (toutes, ou celles des clés indiquées).
     *
     * @param array{trace: ?Trace, scolarite: EtudiantScolarite, cibles: list<CibleLot>} $portee
     * @param list<string>|null                                                          $cles
     */
    public function rouvrir(Personnel $evaluateur, array $portee, ?array $cles = null): int
    {
        $nb = 0;
        $this->em->wrapInTransaction(function () use ($evaluateur, $portee, $cles, &$nb): void {
            foreach ($portee['cibles'] as $c) {
                if ($cles !== null && !\in_array($c->cle, $cles, true)) {
                    continue;
                }
                $e = $this->existante($evaluateur, $portee['scolarite'], $c);
                if ($e !== null && !$e->isModifiable()) {
                    $this->manager->rouvrir($e);
                    ++$nb;
                }
            }
        });

        return $nb;
    }

    /**
     * Demande à l'étudiant de réviser la trace : toutes mes évaluations de la trace passent en « révision
     * demandée » avec le même motif, et l'étudiant n'est prévenu qu'une fois.
     *
     * @return int nombre d'évaluations concernées
     */
    public function demanderRevision(Personnel $evaluateur, Trace $trace, string $motif): int
    {
        if ($trace->getStatut() === StatutTraceEnum::A_REVISER) {
            throw UnifolioValidationException::un('Une révision a déjà été demandée pour cette trace.');
        }
        if (trim($motif) === '') {
            throw UnifolioValidationException::un('Expliquez à l\'étudiant ce qu\'il doit réviser.');
        }
        $portee = $this->porteeTrace($trace);

        $concernees = [];
        $this->em->wrapInTransaction(function () use ($evaluateur, $portee, $motif, &$concernees): void {
            // 1re passe : toutes les évaluations existent et sont modifiables (la trace n'est pas encore « à réviser »).
            foreach ($portee['cibles'] as $c) {
                $e = $this->existante($evaluateur, $portee['scolarite'], $c) ?? $this->ouvrir($evaluateur, $portee['scolarite'], $c, exigerAutoEvaluation: false);
                $concernees[] = $e->isModifiable() ? $e : $this->manager->rouvrir($e);
            }
            // 2e passe : la demande de révision proprement dite.
            foreach ($concernees as $e) {
                $this->manager->demanderRevision($e, $motif, notifier: false);
            }
        });
        if ($concernees !== []) {
            $this->notifier->revisionDemandee($concernees[0]);
        }

        return \count($concernees);
    }

    // ---------- Outils ----------

    private function existante(Personnel $evaluateur, EtudiantScolarite $scolarite, CibleLot $c): ?Evaluation
    {
        return $c->rattachement !== null
            ? $this->evaluations->findOneParEvaluateurEtRattachement($evaluateur, $c->rattachement)
            : $this->evaluations->findBilan($evaluateur, $scolarite, $c->granularite, (int) $c->cible->getId());
    }

    private function ouvrir(Personnel $evaluateur, EtudiantScolarite $scolarite, CibleLot $c, bool $exigerAutoEvaluation = true): Evaluation
    {
        return $c->rattachement !== null
            ? $this->manager->ouvrirPourRattachement($evaluateur, $c->rattachement, $exigerAutoEvaluation)
            : $this->manager->ouvrirBilan($evaluateur, $scolarite, $c->granularite->value, (int) $c->cible->getId());
    }

    /** Un point est renseigné s'il porte au moins une réponse non vide ou une appréciation. */
    private function estRenseigne(array $item): bool
    {
        if (trim((string) ($item['appreciation'] ?? '')) !== '') {
            return true;
        }
        foreach (\is_array($item['reponses'] ?? null) ? $item['reponses'] : [] as $r) {
            if (!\is_array($r)) {
                continue;
            }
            foreach (['echelon', 'valeur', 'booleen'] as $champ) {
                if (isset($r[$champ]) && $r[$champ] !== '') {
                    return true;
                }
            }
            if (trim((string) ($r['texte'] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, mixed> */
    private function contenuGrille(\UnifolioBundle\Entity\GrilleVersion $version): array
    {
        $contenu = $this->grilles->contenu($version);
        unset($contenu['erreursPublication']);

        return ['libelle' => $version->getGrille()->getLibelle(), 'version' => $version->getNumero()] + $contenu;
    }
}
