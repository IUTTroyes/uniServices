<?php

namespace FinanceBundle\Service\Notification;

use App\Entity\Users\Personnel;
use App\Notification\NotificationService;
use App\Service\Email\EmailService;
use FinanceBundle\Entity\FinanceBonCommande;
use FinanceBundle\Enum\ActionWorkflowEnum as Action;
use FinanceBundle\Enum\TypeNotificationEnum as Type;
use FinanceBundle\Security\FinanceAccess;
use Psr\Log\LoggerInterface;

/**
 * Traduit le circuit d'une demande d'achat en notifications (cloche + e-mail selon les préférences de chacun).
 * À appeler APRÈS le flush. Ne notifie jamais l'auteur de l'action.
 */
class FinanceNotifier
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly FinanceDestinataires $destinataires,
        private readonly EmailService $emails,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function url(FinanceBonCommande $commande): string
    {
        return '/app/finance/demande/'.$commande->getId();
    }

    /** Après une action du circuit. */
    public function apresAction(FinanceBonCommande $commande, Action $action, Personnel $auteur): void
    {
        $objet = mb_strimwidth((string) $commande->getObjetDemande(), 0, 120, '…');
        $demandeur = $commande->getDemandeur();
        $departement = $commande->getDepartement();

        switch ($action) {
            case Action::SOUMETTRE:
                $texte = sprintf('%s a soumis la demande d\'achat « %s » (%s) : elle attend votre avis.', $demandeur?->getDisplay() ?? 'Un demandeur', $objet, $this->montant($commande));
                $this->notifierTous($this->destinataires->avecRole($departement, FinanceAccess::ROLE_VALIDEUR), Type::DEMANDE_A_VISER, $texte, $commande, $auteur);
                break;
            case Action::AVIS_FAVORABLE:
                $this->notifier($demandeur, Type::AVIS_RENDU, sprintf('Avis favorable sur votre demande d\'achat « %s » : elle passe au service financier.', $objet), $commande, $auteur);
                $this->notifierTous(
                    $this->destinataires->avecRole($departement, FinanceAccess::ROLE_GESTIONNAIRE),
                    Type::DEMANDE_A_VERIFIER,
                    sprintf('La demande d\'achat « %s » (%s, %s) a reçu un avis favorable : elle est à vérifier.', $objet, $departement?->getLibelle() ?? '—', $this->montant($commande)),
                    $commande,
                    $auteur,
                );
                break;
            case Action::DEMANDER_INFORMATION:
                $this->notifier($demandeur, Type::AVIS_RENDU, sprintf('Des précisions sont demandées sur votre demande d\'achat « %s » : %s', $objet, $this->extrait($commande->getAvisDirecteurCommentaire())), $commande, $auteur);
                break;
            case Action::AVIS_DEFAVORABLE:
            case Action::REJETER:
                $this->notifier($demandeur, Type::DEMANDE_REJETEE, sprintf('Votre demande d\'achat « %s » est rejetée : %s', $objet, $this->extrait($commande->getMotifRejet())), $commande, $auteur);
                break;
            case Action::VISER:
                $this->notifier($demandeur, Type::COMMANDE_ENGAGEE, sprintf('Votre demande d\'achat « %s » est engagée : la commande va être passée.', $objet), $commande, $auteur);
                break;
            case Action::TRANSMETTRE:
                $texte = sprintf('La commande « %s » est transmise au fournisseur. À la livraison, constatez le service fait.', $objet);
                $this->notifierTous(array_filter([$demandeur, $commande->getResponsable()]), Type::SERVICE_FAIT_A_CONSTATER, $texte, $commande, $auteur);
                break;
            case Action::CONSTATER_SERVICE_FAIT:
                $this->notifierTous(
                    $this->destinataires->avecRole($departement, FinanceAccess::ROLE_GESTIONNAIRE),
                    Type::SERVICE_FAIT_A_CERTIFIER,
                    sprintf('%s a constaté la réception de la commande « %s » : le service fait est à certifier.', $auteur->getDisplay(), $objet),
                    $commande,
                    $auteur,
                );
                break;
            case Action::PAYER:
                $this->notifier($demandeur, Type::COMMANDE_PAYEE, sprintf('Le paiement de la commande « %s » est enregistré%s.', $objet, $commande->isFactureFinale() ? ' : la commande est soldée' : ''), $commande, $auteur);
                break;
            default:
                break;
        }
    }

    /**
     * Récapitulatif périodique des demandes en attente (une notification, et l'e-mail dédié « finance.recapitulatif »
     * si la personne le reçoit « tout de suite »).
     *
     * @param array{total: int, resume: string, groupes: list<array{statut: string, nombre: int, lignes: list<array<string, string>>, autres: int}>} $recapitulatif
     */
    public function recapitulatif(Personnel $destinataire, array $recapitulatif): void
    {
        $adresse = $destinataire->getMailUniv();
        $this->notifications->notifier(
            $destinataire,
            Type::RECAPITULATIF->cle(),
            sprintf('%d demande(s) d\'achat en attente : %s.', $recapitulatif['total'], $recapitulatif['resume']),
            '/app/finance',
            $adresse ? function () use ($destinataire, $adresse, $recapitulatif): bool {
                try {
                    $this->emails->send(Type::RECAPITULATIF->cle(), $adresse, [
                        'destinataire_nom' => $destinataire->getDisplay(),
                        'total' => $recapitulatif['total'],
                        'resume' => $recapitulatif['resume'],
                        'groupes' => $recapitulatif['groupes'],
                        'lien' => '/app/finance',
                    ]);

                    return true;
                } catch (\Throwable $e) {
                    $this->logger->warning('Récapitulatif finance non envoyé : '.$e->getMessage(), ['personnel' => $destinataire->getId()]);

                    return false;
                }
            } : null,
        );
    }

    /** @param iterable<Personnel|null> $destinataires */
    private function notifierTous(iterable $destinataires, Type $type, string $texte, FinanceBonCommande $commande, Personnel $auteur): void
    {
        $deja = [];
        foreach ($destinataires as $destinataire) {
            if ($destinataire !== null && !isset($deja[$destinataire->getId()])) {
                $deja[$destinataire->getId()] = true;
                $this->notifier($destinataire, $type, $texte, $commande, $auteur);
            }
        }
    }

    private function notifier(?Personnel $destinataire, Type $type, string $texte, FinanceBonCommande $commande, Personnel $auteur): void
    {
        if ($destinataire === null || FinanceAccess::memePersonne($destinataire, $auteur)) {
            return;
        }
        $this->notifications->notifier($destinataire, $type->cle(), $texte, self::url($commande));
    }

    private function montant(FinanceBonCommande $commande): string
    {
        $montant = $commande->getMontantTtc() ?? $commande->getMontantHt();

        return $montant === null ? 'montant non renseigné' : number_format((float) $montant, 2, ',', ' ').' € '.($commande->getMontantTtc() !== null ? 'TTC' : 'HT');
    }

    private function extrait(?string $texte): string
    {
        $texte = trim((string) $texte);

        return $texte === '' ? 'sans motif précisé.' : '« '.mb_strimwidth($texte, 0, 300, '…').' »';
    }
}
