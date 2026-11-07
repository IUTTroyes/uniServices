<?php

namespace App\Notification;

use App\Entity\Notification\Notification;
use App\Entity\Notification\PreferenceNotification;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Enum\ModeNotificationEnum;
use App\Service\Email\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Point d'entrée UNIQUE pour notifier quelqu'un, depuis n'importe quel package.
 *
 *  - la cloche de l'application reçoit TOUJOURS la notification (journal) ;
 *  - l'e-mail suit les préférences de la personne pour ce type : tout de suite, dans le résumé groupé, ou jamais ;
 *  - une notification ne fait jamais échouer l'action qui la déclenche (toute erreur est journalisée puis absorbée).
 *
 * Voir docs/NOTIFICATIONS_SYSTEM.md.
 */
class NotificationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly NotificationTypeRegistry $types,
        private readonly EmailService $emails,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param string                  $typeCle       clé déclarée par un NotificationTypeProviderInterface (« unifolio.commentaire_enseignant »)
     * @param string                  $texte         phrase lisible, déjà complète : « Alice a commenté votre trace « X ». »
     * @param string|null             $url           chemin interne à ouvrir au clic (« /app/unifolio/traces/… »)
     * @param (callable(): bool)|null $envoiImmediat e-mail dédié (gabarit propre) quand le mode est « tout de suite » ;
     *                                               à défaut, l'e-mail générique « notification.immediate » est utilisé
     */
    public function notifier(Etudiant|Personnel $destinataire, string $typeCle, string $texte, ?string $url = null, ?callable $envoiImmediat = null): ?Notification
    {
        try {
            $type = $this->types->get($typeCle);
            if ($type === null) {
                $this->logger->warning('Notification ignorée : type inconnu.', ['type' => $typeCle]);

                return null;
            }
            if (($type->audience === NotificationType::AUDIENCE_ETUDIANT) !== ($destinataire instanceof Etudiant)) {
                $this->logger->warning('Notification ignorée : destinataire hors du public du type.', ['type' => $typeCle]);

                return null;
            }

            $mode = $this->preferences($destinataire)->modePour($type);
            $notification = $this->journaliser($destinataire, $type, $texte, $url, $mode);

            if ($mode === ModeNotificationEnum::IMMEDIAT) {
                $envoye = $envoiImmediat !== null ? $envoiImmediat() : $this->envoyerGenerique($destinataire, $type, $texte, $url);
                // Sans adresse, aucun e-mail n'est possible : inutile de garder l'événement pour un résumé qui ne partira pas.
                if ($envoye || self::adresse($destinataire) === null) {
                    $notification->marquerEmailTraite();
                    $this->em->flush();
                }
            }

            return $notification;
        } catch (\Throwable $e) {
            $this->logger->warning('Notification non traitée.', ['type' => $typeCle, 'erreur' => $e->getMessage()]);

            return null;
        }
    }

    public function preferences(Etudiant|Personnel $personne): PreferenceNotification
    {
        $cle = $personne instanceof Etudiant ? 'etudiant' : 'personnel';

        return $this->em->getRepository(PreferenceNotification::class)->findOneBy([$cle => $personne]) ?? new PreferenceNotification($personne);
    }

    /** Adresse e-mail d'une personne (universitaire, à défaut personnelle). */
    public static function adresse(Etudiant|Personnel $personne): ?string
    {
        $a = $personne->getMailUniv() ?? $personne->getMailPerso();

        return $a !== null && $a !== '' ? $a : null;
    }

    private function journaliser(Etudiant|Personnel $destinataire, NotificationType $type, string $texte, ?string $url, ModeNotificationEnum $mode): Notification
    {
        // En attente d'un résumé : un événement identique (même type, même lien) est fusionné au lieu d'ajouter une ligne.
        if ($mode === ModeNotificationEnum::RESUME) {
            $cle = $destinataire instanceof Etudiant ? 'etudiant' : 'personnel';
            $existante = $this->em->getRepository(Notification::class)->findOneBy([$cle => $destinataire, 'type' => $type->cle, 'url' => $url, 'emailTraiteLe' => null]);
            if ($existante !== null) {
                $existante->fusionner($texte);
                $this->em->flush();

                return $existante;
            }
        }
        $notification = new Notification($destinataire, $type->cle, $type->source, $texte, $url);
        if ($mode === ModeNotificationEnum::AUCUN) {
            $notification->marquerEmailTraite(); // jamais d'e-mail : seule la cloche est servie
        }
        $this->em->persist($notification);
        $this->em->flush();

        return $notification;
    }

    private function envoyerGenerique(Etudiant|Personnel $destinataire, NotificationType $type, string $texte, ?string $url): bool
    {
        $to = self::adresse($destinataire);
        if ($to === null) {
            return false;
        }
        $this->emails->send('notification.immediate', $to, [
            'destinataire_nom' => $destinataire->getDisplay(),
            'source' => $type->sourceLibelle,
            'titre' => $type->rubrique,
            'texte' => $texte,
            'urlNotification' => $url ?? '/app',
        ]);

        return true;
    }
}
