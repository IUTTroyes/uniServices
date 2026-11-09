<?php

namespace FinanceBundle\Workflow;

use App\Entity\Users\Personnel;
use FinanceBundle\Entity\FinanceBonCommande;
use FinanceBundle\Entity\FinanceReception;
use FinanceBundle\Enum\ActionWorkflowEnum as Action;
use FinanceBundle\Enum\AvisDirecteurEnum;
use FinanceBundle\Enum\StatutCommandeEnum as Statut;
use FinanceBundle\Enum\TypeReceptionEnum;
use FinanceBundle\Security\FinanceAccess;

/**
 * Circuit d'une demande d'achat, entièrement côté serveur : qui peut faire quelle action depuis quel statut, et quels
 * champs chacun peut modifier à chaque étape. Le serveur fixe statuts, dates et signataires.
 *
 *  BROUILLON ─soumettre→ SOUMIS ─avis favorable→ VERIFICATION_FINANCIERE ─vérifier→ VISA_ENGAGEMENT ─viser→
 *  COMMANDE_VALIDEE ─transmettre→ COMMANDE_TRANSMISE ─constater→ SERVICE_FAIT_CONSTATE ─certifier→
 *  SERVICE_FAIT_CERTIFIE ─facture→ FACTURE_RECUE ─payer→ PAYE ─clôturer→ CLOTURE
 *  Demande de précisions → BROUILLON ; avis défavorable ou rejet → REJETE ─rouvrir→ BROUILLON ;
 *  facture rejetée → SERVICE_FAIT_CERTIFIE.
 */
class FinanceWorkflow
{
    /** Champs de la demande proprement dite (étape 1). */
    public const CHAMPS_DEMANDE = ['prestation', 'objetDemande', 'montantHt', 'montantTtc', 'tauxTva', 'noteExplicative', 'centreFinancier', 'fournisseur', 'fournisseurPropose', 'responsable', 'service'];
    /** Champs de la vérification et du suivi SIFAC (service financier). */
    public const CHAMPS_SIFAC = ['centreFinancier', 'fournisseur', 'commandeSurMarche', 'numeroBonCommande', 'dateBonCommande', 'dateLivraisonRenseignee', 'pjSurSifac', 'colisAttendu'];
    /** Corrections de la facturation après coup (service financier). */
    public const CHAMPS_FACTURE = ['dateArriveeFacture', 'datePaiement', 'factureFinale'];

    /** @var array<string, array{depuis: list<Statut>, vers: Statut|null}> vers null : statut calculé par l'action */
    private const TRANSITIONS = [
        'soumettre' => ['depuis' => [Statut::BROUILLON], 'vers' => Statut::SOUMIS],
        'avis_favorable' => ['depuis' => [Statut::SOUMIS, Statut::AVIS_DIRECTION], 'vers' => Statut::VERIFICATION_FINANCIERE],
        'avis_defavorable' => ['depuis' => [Statut::SOUMIS, Statut::AVIS_DIRECTION], 'vers' => Statut::REJETE],
        'demander_information' => ['depuis' => [Statut::SOUMIS, Statut::AVIS_DIRECTION], 'vers' => Statut::BROUILLON],
        'verifier' => ['depuis' => [Statut::VERIFICATION_FINANCIERE], 'vers' => Statut::VISA_ENGAGEMENT],
        'viser' => ['depuis' => [Statut::VISA_ENGAGEMENT], 'vers' => Statut::COMMANDE_VALIDEE],
        'transmettre' => ['depuis' => [Statut::COMMANDE_VALIDEE], 'vers' => Statut::COMMANDE_TRANSMISE],
        'constater_service_fait' => ['depuis' => [Statut::COMMANDE_VALIDEE, Statut::COMMANDE_TRANSMISE, Statut::SERVICE_FAIT_CONSTATE], 'vers' => Statut::SERVICE_FAIT_CONSTATE],
        'certifier_service_fait' => ['depuis' => [Statut::SERVICE_FAIT_CONSTATE], 'vers' => Statut::SERVICE_FAIT_CERTIFIE],
        'recevoir_facture' => ['depuis' => [Statut::SERVICE_FAIT_CERTIFIE], 'vers' => Statut::FACTURE_RECUE],
        'rejeter_facture' => ['depuis' => [Statut::FACTURE_RECUE], 'vers' => Statut::SERVICE_FAIT_CERTIFIE],
        'payer' => ['depuis' => [Statut::FACTURE_RECUE], 'vers' => Statut::PAYE],
        'cloturer' => ['depuis' => [Statut::PAYE], 'vers' => Statut::CLOTURE],
        'rejeter' => ['depuis' => [Statut::SOUMIS, Statut::AVIS_DIRECTION, Statut::VERIFICATION_FINANCIERE, Statut::VISA_ENGAGEMENT, Statut::COMMANDE_VALIDEE], 'vers' => Statut::REJETE],
        'reouvrir' => ['depuis' => [Statut::REJETE], 'vers' => Statut::BROUILLON],
    ];

    public function __construct(private readonly FinanceAccess $access)
    {
    }

    /** @return list<Action> actions que cette personne peut faire maintenant sur cette demande */
    public function actionsPossibles(mixed $user, FinanceBonCommande $commande): array
    {
        return array_values(array_filter(Action::cases(), fn (Action $a): bool => $this->peut($user, $commande, $a)));
    }

    public function peut(mixed $user, FinanceBonCommande $commande, Action $action): bool
    {
        if (!\in_array($commande->getStatut(), self::TRANSITIONS[$action->value]['depuis'], true)) {
            return false;
        }
        if ($action === Action::CONSTATER_SERVICE_FAIT && $commande->getStatut() === Statut::SERVICE_FAIT_CONSTATE
            && $commande->getDerniereReception()?->getTypeReception() !== TypeReceptionEnum::PARTIELLE) {
            return false; // nouvelle réception seulement après une réception partielle
        }

        $gestionnaire = $this->access->estGestionnaire($user, $commande);

        return match ($action) {
            Action::SOUMETTRE, Action::REOUVRIR => $gestionnaire || $this->access->estDemandeur($user, $commande),
            Action::AVIS_FAVORABLE, Action::AVIS_DEFAVORABLE, Action::DEMANDER_INFORMATION => $this->access->estValideur($user, $commande),
            Action::CONSTATER_SERVICE_FAIT => $gestionnaire || $this->access->estDemandeur($user, $commande)
                || FinanceAccess::memePersonne($commande->getResponsable(), $user),
            default => $gestionnaire,
        };
    }

    /** Décisions possibles au dépôt du document signé. */
    public const DECISION_FAVORABLE = 'favorable';
    public const DECISION_DEFAVORABLE = 'defavorable';

    /**
     * Le document signé peut-il porter la décision (avis du directeur) ? Tant que l'avis n'est pas rendu, le demandeur
     * qui rapporte le document signé et le service financier peuvent l'enregistrer d'un seul geste.
     */
    public function peutDecider(mixed $user, FinanceBonCommande $commande): bool
    {
        return \in_array($commande->getStatut(), [Statut::BROUILLON, Statut::SOUMIS], true)
            && ($this->access->estDemandeur($user, $commande) || $this->access->estGestionnaire($user, $commande));
    }

    /**
     * Enregistre la décision portée par le document signé déposé (sans flush) : avis favorable → vérification financière ;
     * refus → demande rejetée (motif obligatoire). Le signataire n'est pas connu du serveur : l'avis porte la mention du
     * document et du dépôt.
     *
     * @throws ActionRefuseeException
     * @throws \DomainException
     */
    public function appliquerDecisionDocument(Personnel $user, FinanceBonCommande $commande, string $decision, string $commentaire = ''): Action
    {
        if (!\in_array($commande->getStatut(), [Statut::BROUILLON, Statut::SOUMIS], true)) {
            throw new ActionRefuseeException('L\'avis a déjà été rendu sur cette demande.', false);
        }
        if (!$this->peutDecider($user, $commande)) {
            throw new ActionRefuseeException('Vous ne pouvez pas enregistrer la décision de cette demande.');
        }
        $commentaire = trim($commentaire);
        $maintenant = new \DateTime();
        $mention = sprintf('Décision portée par le document signé, déposé par %s.', $user->getDisplay());

        if ($decision === self::DECISION_FAVORABLE) {
            if (trim((string) $commande->getObjetDemande()) === '' || $commande->getMontantHt() === null) {
                throw new \DomainException('L\'objet et le montant de la demande sont obligatoires.');
            }
            $commande->setDateSignatureDemandeur($commande->getDateSignatureDemandeur() ?? $maintenant)
                ->setAvisDirecteur(AvisDirecteurEnum::FAVORABLE)
                ->setAvisDirecteurCommentaire($commentaire !== '' ? $commentaire."\n".$mention : $mention)
                ->setDateAvisDirecteur($maintenant)
                ->setSignataireDirecteur(null)
                ->setDateSignatureResponsable($maintenant)
                ->setStatut(Statut::VERIFICATION_FINANCIERE);

            return Action::AVIS_FAVORABLE;
        }
        if ($decision === self::DECISION_DEFAVORABLE) {
            if ($commentaire === '') {
                throw new \DomainException('Indiquez le motif du refus.');
            }
            $commande->setAvisDirecteur(AvisDirecteurEnum::DEFAVORABLE)
                ->setAvisDirecteurCommentaire($commentaire."\n".$mention)
                ->setDateAvisDirecteur($maintenant)
                ->setSignataireDirecteur(null)
                ->setDateSignatureResponsable(null)
                ->setMotifRejet($commentaire)
                ->setStatut(Statut::REJETE);

            return Action::AVIS_DEFAVORABLE;
        }
        throw new \DomainException('Décision inconnue.');
    }

    /**
     * Applique l'action (sans flush).
     *
     * @param array<string, mixed> $donnees commentaire, dates, constatation…
     *
     * @throws ActionRefuseeException action impossible depuis ce statut, ou interdite à cette personne
     * @throws \DomainException       données manquantes ou invalides (message affichable)
     */
    public function appliquer(Personnel $user, FinanceBonCommande $commande, Action $action, array $donnees = []): void
    {
        if (!\in_array($commande->getStatut(), self::TRANSITIONS[$action->value]['depuis'], true)) {
            throw new ActionRefuseeException(sprintf('« %s » est impossible au statut « %s ».', $action->getLibelle(), $commande->getStatut()->getLabel()), false);
        }
        if (!$this->peut($user, $commande, $action)) {
            throw new ActionRefuseeException(sprintf('Vous ne pouvez pas faire « %s » sur cette demande.', $action->getLibelle()));
        }
        $commentaire = trim((string) ($donnees['commentaire'] ?? ''));
        if ($action->exigeCommentaire() && $commentaire === '') {
            throw new \DomainException('Un commentaire est obligatoire pour cette action.');
        }
        $maintenant = new \DateTime();

        switch ($action) {
            case Action::SOUMETTRE:
                if (trim((string) $commande->getObjetDemande()) === '' || $commande->getMontantHt() === null) {
                    throw new \DomainException('L\'objet et le montant de la demande sont obligatoires.');
                }
                $commande->setDateDemande($maintenant)->setDateSignatureDemandeur($maintenant)
                    ->setAvisDirecteur(null)->setAvisDirecteurCommentaire(null)->setDateAvisDirecteur(null)->setSignataireDirecteur(null);
                break;
            case Action::AVIS_FAVORABLE:
            case Action::AVIS_DEFAVORABLE:
            case Action::DEMANDER_INFORMATION:
                $commande->setAvisDirecteur(match ($action) {
                    Action::AVIS_FAVORABLE => AvisDirecteurEnum::FAVORABLE,
                    Action::AVIS_DEFAVORABLE => AvisDirecteurEnum::DEFAVORABLE,
                    default => AvisDirecteurEnum::DEMANDE_INFO,
                })
                    ->setAvisDirecteurCommentaire($commentaire !== '' ? $commentaire : null)
                    ->setDateAvisDirecteur($maintenant)
                    ->setSignataireDirecteur($user)
                    ->setDateSignatureResponsable($action === Action::AVIS_FAVORABLE ? $maintenant : null);
                if ($action === Action::AVIS_DEFAVORABLE) {
                    $commande->setMotifRejet($commentaire);
                }
                break;
            case Action::VERIFIER:
                if (trim((string) $commande->getNumeroBonCommande()) === '') {
                    throw new \DomainException('Le numéro de bon de commande SIFAC est obligatoire.');
                }
                if ($commande->getFournisseur() === null) {
                    throw new \DomainException('Rattachez la demande à un fournisseur du référentiel.');
                }
                $commande->setDateVerificationFinanciere($maintenant)->setVerificateurFinancier($user)->setGestionnaire($user);
                break;
            case Action::VISER:
                $commande->setDateVisaEngagement($maintenant)->setSignataireVisaEngagement($user);
                break;
            case Action::TRANSMETTRE:
                $commande->setBcTransmis(true);
                if ($commande->getDateBonCommande() === null) {
                    $commande->setDateBonCommande($maintenant);
                }
                break;
            case Action::CONSTATER_SERVICE_FAIT:
                $commande->addReception($this->reception($user, $commande, $donnees, $maintenant));
                break;
            case Action::CERTIFIER_SERVICE_FAIT:
                $commande->setDateCertificationServiceFait($maintenant)->setCertificateurServiceFait($user);
                break;
            case Action::RECEVOIR_FACTURE:
                $commande->setDateArriveeFacture($this->date($donnees['date'] ?? null, $maintenant))->setRejetFactureMotif(null);
                break;
            case Action::REJETER_FACTURE:
                $commande->setRejetFactureMotif($commentaire)->setDateArriveeFacture(null);
                break;
            case Action::PAYER:
                $commande->setDatePaiement($this->date($donnees['date'] ?? null, $maintenant))
                    ->setFactureFinale((bool) ($donnees['factureFinale'] ?? true));
                break;
            case Action::CLOTURER:
                break;
            case Action::REJETER:
                $commande->setMotifRejet($commentaire);
                break;
            case Action::REOUVRIR:
                $commande->setMotifRejet(null);
                break;
        }

        $commande->setStatut(self::TRANSITIONS[$action->value]['vers']);
    }

    /**
     * Champs que cette personne peut modifier (PATCH) au statut actuel de la demande.
     *
     * @return list<string>
     */
    public function champsModifiables(mixed $user, FinanceBonCommande $commande): array
    {
        $statut = $commande->getStatut();
        $champs = [];
        if ($this->access->estGestionnaire($user, $commande)) {
            $champs = match ($statut) {
                Statut::BROUILLON => self::CHAMPS_DEMANDE,
                Statut::SOUMIS, Statut::AVIS_DIRECTION, Statut::VERIFICATION_FINANCIERE, Statut::VISA_ENGAGEMENT => [...self::CHAMPS_DEMANDE, ...self::CHAMPS_SIFAC],
                Statut::COMMANDE_VALIDEE, Statut::COMMANDE_TRANSMISE, Statut::SERVICE_FAIT_CONSTATE, Statut::SERVICE_FAIT_CERTIFIE => self::CHAMPS_SIFAC,
                Statut::FACTURE_RECUE, Statut::PAYE => [...self::CHAMPS_SIFAC, ...self::CHAMPS_FACTURE],
                Statut::CLOTURE, Statut::REJETE => [],
            };
        } elseif ($statut === Statut::BROUILLON && $this->access->estDemandeur($user, $commande)) {
            $champs = self::CHAMPS_DEMANDE;
        }

        return array_values(array_unique($champs));
    }

    /** Champs autorisés à la création (la demande naît en brouillon). */
    public static function champsCreation(): array
    {
        return [...self::CHAMPS_DEMANDE, 'departement'];
    }

    /** @param array<string, mixed> $donnees */
    private function reception(Personnel $user, FinanceBonCommande $commande, array $donnees, \DateTime $maintenant): FinanceReception
    {
        $type = TypeReceptionEnum::tryFrom((string) ($donnees['typeReception'] ?? TypeReceptionEnum::TOTALE->value));
        if ($type === null) {
            throw new \DomainException('Type de réception inconnu.');
        }
        $details = trim((string) ($donnees['detailsReceptionPartielle'] ?? ''));
        if ($type === TypeReceptionEnum::PARTIELLE && $details === '') {
            throw new \DomainException('Précisez ce qui a été reçu pour une réception partielle.');
        }
        $montant = $donnees['montantTtcConstate'] ?? null;
        if ($montant !== null && $montant !== '' && (!is_numeric($montant) || (float) $montant < 0)) {
            throw new \DomainException('Le montant constaté doit être un nombre positif.');
        }
        $date = $this->date($donnees['dateEffectiveReception'] ?? null, $maintenant);
        if ($date > $maintenant) {
            throw new \DomainException('La date de réception ne peut pas être dans le futur.');
        }

        return (new FinanceReception())
            ->setTypeReception($type)
            ->setDateEffectiveReception($date)
            ->setDateSignatureServiceFait($maintenant)
            ->setMontantTtcConstate($montant === null || $montant === '' ? $commande->getMontantTtc() : number_format((float) $montant, 2, '.', ''))
            ->setPrestationsConformes((bool) ($donnees['prestationsConformes'] ?? true))
            ->setDetailsReceptionPartielle($details !== '' ? $details : null)
            ->setQualiteReceptionnaire(($q = trim((string) ($donnees['qualiteReceptionnaire'] ?? ''))) !== '' ? mb_substr($q, 0, 100) : null)
            ->setReceptionnaire($user);
    }

    private function date(mixed $valeur, \DateTime $defaut): \DateTime
    {
        if ($valeur === null || $valeur === '') {
            return clone $defaut;
        }
        $date = \is_string($valeur) ? \DateTime::createFromFormat('!Y-m-d', substr($valeur, 0, 10)) : false;
        if ($date === false) {
            throw new \DomainException('Date invalide (format attendu : AAAA-MM-JJ).');
        }

        return $date;
    }
}
