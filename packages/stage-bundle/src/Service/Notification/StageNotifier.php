<?php

namespace StageBundle\Service\Notification;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Notification\NotificationService;
use StageBundle\Entity\Stages\StageAvenant;
use StageBundle\Entity\Stages\StageEtudiant;
use StageBundle\Entity\Stages\StageSoutenance;
use StageBundle\Enum\EtatStageEnum;
use StageBundle\Enum\TypeNotificationEnum as Type;
use StageBundle\Security\StageAccess;

/**
 * Traduit les événements métier des stages en notifications (cloche + e-mail selon les préférences de chacun).
 * À appeler APRÈS le flush de l'action. Ne notifie jamais l'auteur de l'action.
 */
class StageNotifier
{
    private const URL_ETUDIANT = '/app/stage/etudiant';
    private const URL_TUTEUR = '/app/stage/enseignant';

    public function __construct(private readonly NotificationService $notifications)
    {
    }

    /**
     * Après création / modification d'un dossier de stage.
     *
     * @param EtatStageEnum|null $etatAvant   null pour une création
     * @param Personnel|null     $tuteurAvant tuteur universitaire avant modification
     */
    public function dossierEnregistre(StageEtudiant $stage, ?EtatStageEnum $etatAvant, ?Personnel $tuteurAvant, Etudiant|Personnel|null $auteur): void
    {
        $etudiant = $stage->getEtudiant();
        $etat = $stage->getEtatStage();
        $periode = $stage->getStagePeriode()?->getLibelle() ?? 'votre période de stage';
        $entreprise = $stage->getEntreprise()?->getRaisonSociale();

        // Dépôt (ou nouvelle version) d'une demande par l'étudiant, ou passage « en attente » par un responsable.
        if ($etat === EtatStageEnum::DEPOSE && ($etatAvant !== EtatStageEnum::DEPOSE || $auteur instanceof Etudiant) && $etudiant !== null) {
            $texte = sprintf(
                '%s a %s sa demande de convention%s (%s).',
                $etudiant->getDisplay(),
                $etatAvant === EtatStageEnum::DEPOSE ? 'modifié' : 'déposé',
                $entreprise ? ' chez '.$entreprise : '',
                $periode
            );
            foreach ($this->responsables($stage, $auteur) as $responsable) {
                $this->notifications->notifier($responsable, Type::DEMANDE_DEPOSEE->cle(), $texte, $this->urlPeriode($stage));
            }
        }

        if ($etudiant !== null && $etat !== $etatAvant && !StageAccess::memePersonne($etudiant, $auteur)) {
            $notif = match ($etat) {
                EtatStageEnum::AUTORISE => [Type::SAISIE_OUVERTE, sprintf('Vous pouvez saisir votre demande de convention de stage (%s).', $periode)],
                EtatStageEnum::VALIDE => [Type::CONVENTION_VALIDEE, sprintf('Votre demande de convention%s a été validée (%s).', $entreprise ? ' chez '.$entreprise : '', $periode)],
                EtatStageEnum::REFUS => [Type::CONVENTION_REFUSEE, sprintf(
                    'Votre demande de convention%s a été refusée%s Corrigez-la puis redéposez-la (%s).',
                    $entreprise ? ' chez '.$entreprise : '',
                    $stage->getMotifRefus() ? ' : « '.mb_strimwidth($stage->getMotifRefus(), 0, 300, '…').' ».' : '.',
                    $periode
                )],
                EtatStageEnum::CONVENTION_IMPRIMEE, EtatStageEnum::CONVENTION_ENVOYEE => \in_array($etatAvant, [EtatStageEnum::CONVENTION_IMPRIMEE, EtatStageEnum::CONVENTION_ENVOYEE], true)
                    ? null
                    : [Type::CONVENTION_A_SIGNER, sprintf('Votre convention de stage est éditée et envoyée pour signature (%s).', $periode)],
                EtatStageEnum::CONVENTION_RECUE => [Type::CONVENTION_SIGNEE, sprintf('Votre convention de stage signée a été reçue : votre dossier est complet (%s).', $periode)],
                default => null,
            };
            if ($notif !== null) {
                $this->notifications->notifier($etudiant, $notif[0]->cle(), $notif[1], self::URL_ETUDIANT);
            }
        }

        $tuteur = $stage->getTuteurUniversitaire();
        if ($tuteur !== null && !StageAccess::memePersonne($tuteur, $tuteurAvant) && !StageAccess::memePersonne($tuteur, $auteur) && $etudiant !== null) {
            $this->notifications->notifier(
                $tuteur,
                Type::TUTEUR_AFFECTE->cle(),
                sprintf('Vous êtes tuteur universitaire du stage de %s%s (%s).', $etudiant->getDisplay(), $entreprise ? ' chez '.$entreprise : '', $periode),
                self::URL_TUTEUR
            );
        }
    }

    public function avenantDemande(StageAvenant $avenant, Etudiant|Personnel|null $auteur): void
    {
        $stage = $avenant->getStageEtudiant();
        if ($stage === null || $stage->getEtudiant() === null) {
            return;
        }
        $texte = sprintf('%s demande un avenant à sa convention : « %s ».', $stage->getEtudiant()->getDisplay(), $avenant->getLibelle() ?? 'avenant');
        foreach ($this->responsables($stage, $auteur) as $responsable) {
            $this->notifications->notifier($responsable, Type::AVENANT_DEMANDE->cle(), $texte, $this->urlPeriode($stage));
        }
    }

    public function avenantValide(StageAvenant $avenant, Etudiant|Personnel|null $auteur): void
    {
        $etudiant = $avenant->getStageEtudiant()?->getEtudiant();
        if ($etudiant === null || StageAccess::memePersonne($etudiant, $auteur)) {
            return;
        }
        $this->notifications->notifier(
            $etudiant,
            Type::AVENANT_VALIDE->cle(),
            sprintf('Votre avenant « %s » a été validé ; les modifications sont appliquées à votre convention.', $avenant->getLibelle() ?? 'avenant'),
            self::URL_ETUDIANT
        );
    }

    /** Créneau attribué à un étudiant (ou modifié) : l'étudiant, son tuteur universitaire et l'assesseur sont prévenus. */
    public function soutenancePlanifiee(StageSoutenance $soutenance, Etudiant|Personnel|null $auteur): void
    {
        $stage = $soutenance->getStageEtudiant();
        $etudiant = $stage?->getEtudiant();
        $date = $soutenance->getDateSoutenance();
        if ($stage === null || $etudiant === null || $date === null) {
            return;
        }
        $quand = sprintf('le %s à %s%s', $date->format('d/m/Y'), $date->format('H:i'), $soutenance->getSalle() ? ' (salle '.$soutenance->getSalle().')' : '');

        if (!StageAccess::memePersonne($etudiant, $auteur)) {
            $this->notifications->notifier($etudiant, Type::SOUTENANCE_PLANIFIEE->cle(), sprintf('Votre soutenance de stage est planifiée %s.', $quand), self::URL_ETUDIANT);
        }

        $jury = [];
        foreach ([$stage->getTuteurUniversitaire(), $soutenance->getEnseignantJury()] as $membre) {
            if ($membre !== null && !StageAccess::memePersonne($membre, $auteur)) {
                $jury[$membre->getId() ?? spl_object_id($membre)] = $membre;
            }
        }
        foreach ($jury as $membre) {
            $this->notifications->notifier(
                $membre,
                Type::JURY_SOUTENANCE->cle(),
                sprintf('Soutenance de stage de %s %s.', $etudiant->getDisplay(), $quand),
                self::URL_TUTEUR
            );
        }
    }

    /** Rapport déposé (ou remplacé) : le tuteur universitaire est prévenu. */
    public function rapportDepose(StageEtudiant $stage, Etudiant|Personnel|null $auteur): void
    {
        $tuteur = $stage->getTuteurUniversitaire();
        $etudiant = $stage->getEtudiant();
        if ($tuteur === null || $etudiant === null || StageAccess::memePersonne($tuteur, $auteur)) {
            return;
        }
        $this->notifications->notifier(
            $tuteur,
            Type::RAPPORT_DEPOSE->cle(),
            sprintf('%s a déposé son rapport de stage (%s).', $etudiant->getDisplay(), $stage->getStagePeriode()?->getLibelle() ?? 'stage'),
            self::URL_TUTEUR
        );
    }

    /** Évaluation rendue par le tuteur en entreprise : le tuteur universitaire et l'expéditeur de la demande sont prévenus. */
    public function evaluationEntrepriseRecue(StageEtudiant $stage): void
    {
        $tuteur = $stage->getTuteurUniversitaire();
        $etudiant = $stage->getEtudiant();
        if ($tuteur === null || $etudiant === null) {
            return;
        }
        $this->notifications->notifier(
            $tuteur,
            Type::EVALUATION_ENTREPRISE_RECUE->cle(),
            sprintf('Le tuteur en entreprise de %s a rendu son évaluation du stage.', $etudiant->getDisplay()),
            self::URL_TUTEUR
        );
    }

    // ---------- Relances (stage:relances) ----------

    /** @param list<StageEtudiant> $enAttente demandes d'une même période */
    public function relanceDemandes(Personnel $responsable, array $enAttente, int $jours): void
    {
        $periode = $enAttente[0]->getStagePeriode();
        $this->notifications->notifier(
            $responsable,
            Type::RELANCE_DEMANDES->cle(),
            sprintf(
                '%d demande(s) de convention attendent une validation depuis plus de %d jours (%s).',
                \count($enAttente), $jours, $periode?->getLibelle() ?? 'période de stage'
            ),
            $this->urlPeriode($enAttente[0])
        );
    }

    public function relanceConvention(StageEtudiant $stage): void
    {
        $etudiant = $stage->getEtudiant();
        if ($etudiant === null) {
            return;
        }
        $this->notifications->notifier(
            $etudiant,
            Type::RELANCE_CONVENTION->cle(),
            sprintf(
                'Votre convention de stage, envoyée pour signature le %s, n\'est pas encore revenue signée : relancez votre organisme d\'accueil (%s).',
                $stage->getDateConventionEnvoyee()?->format('d/m/Y') ?? '?', $stage->getStagePeriode()?->getLibelle() ?? 'stage'
            ),
            self::URL_ETUDIANT
        );
    }

    public function relanceRapport(StageEtudiant $stage, ?\DateTimeInterface $soutenance): void
    {
        $etudiant = $stage->getEtudiant();
        if ($etudiant === null) {
            return;
        }
        $this->notifications->notifier(
            $etudiant,
            Type::RELANCE_RAPPORT->cle(),
            $soutenance !== null
                ? sprintf('Votre soutenance a lieu le %s et votre rapport de stage n\'est pas encore déposé.', $soutenance->format('d/m/Y à H:i'))
                : sprintf('Votre stage est terminé et votre rapport n\'est pas encore déposé (%s).', $stage->getStagePeriode()?->getLibelle() ?? 'stage'),
            self::URL_ETUDIANT
        );
    }

    /** @return list<Personnel> responsables (principal + co-responsables) de la période, sans l'auteur, sans doublon */
    public function responsables(StageEtudiant $stage, Etudiant|Personnel|null $auteur = null): array
    {
        $periode = $stage->getStagePeriode();
        if ($periode === null) {
            return [];
        }
        $liste = [];
        foreach ([$periode->getResponsablePrincipal(), ...$periode->getCoResponsables()->toArray()] as $p) {
            if ($p instanceof Personnel && !StageAccess::memePersonne($p, $auteur)) {
                $liste[$p->getId() ?? spl_object_id($p)] = $p;
            }
        }

        return array_values($liste);
    }

    /** Lien stable par période : les dépôts d'une même période fusionnent dans le résumé (« ×N »). */
    private function urlPeriode(StageEtudiant $stage): string
    {
        $id = $stage->getStagePeriode()?->getId();

        return $id !== null ? '/app/stage/responsable/periode/'.$id : '/app/stage/responsable';
    }
}
