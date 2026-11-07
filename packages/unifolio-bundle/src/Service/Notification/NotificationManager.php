<?php

namespace UnifolioBundle\Service\Notification;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Service\Email\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use UnifolioBundle\Entity\NotificationEnAttente;
use UnifolioBundle\Entity\PreferenceNotification;
use UnifolioBundle\Enum\ModeNotificationEnum;
use UnifolioBundle\Enum\TypeNotificationEnum;

/**
 * Point d'entrée unique des notifications : applique les préférences de la personne.
 *  - « jamais »     : rien n'est envoyé ni conservé ;
 *  - « tout de suite » : e-mail immédiat ;
 *  - « résumé »     : l'événement est mis de côté (fusionné avec les identiques) et part dans le prochain résumé.
 * Une notification ne fait jamais échouer l'action qui la déclenche.
 */
class NotificationManager
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EmailService $emails,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function preferences(Etudiant|Personnel $personne): PreferenceNotification
    {
        $cle = $personne instanceof Etudiant ? 'etudiant' : 'personnel';

        return $this->em->getRepository(PreferenceNotification::class)->findOneBy([$cle => $personne]) ?? new PreferenceNotification($personne);
    }

    public function modePour(Etudiant|Personnel $personne, TypeNotificationEnum $type): ModeNotificationEnum
    {
        return $this->preferences($personne)->modePour($type);
    }

    /**
     * @param (callable(): bool)|null $envoiImmediat envoi dédié au type (gabarit propre) ; à défaut, e-mail générique
     *
     * @return bool true si la notification a été envoyée ou mise de côté
     */
    public function notifier(Etudiant|Personnel $destinataire, TypeNotificationEnum $type, string $texte, ?string $url = null, ?callable $envoiImmediat = null): bool
    {
        try {
            $mode = $this->modePour($destinataire, $type);

            return match ($mode) {
                ModeNotificationEnum::AUCUN => false,
                ModeNotificationEnum::RESUME => $this->mettreDeCote($destinataire, $type, $texte, $url),
                ModeNotificationEnum::IMMEDIAT => $envoiImmediat !== null ? $envoiImmediat() : $this->envoyerGenerique($destinataire, $type, $texte, $url),
            };
        } catch (\Throwable $e) {
            $this->logger->warning('UniFolio : notification non traitée.', ['type' => $type->value, 'erreur' => $e->getMessage()]);

            return false;
        }
    }

    private function mettreDeCote(Etudiant|Personnel $destinataire, TypeNotificationEnum $type, string $texte, ?string $url): bool
    {
        $cle = $destinataire instanceof Etudiant ? 'etudiant' : 'personnel';
        $existante = $this->em->getRepository(NotificationEnAttente::class)->findOneBy([$cle => $destinataire, 'type' => $type, 'url' => $url, 'envoyeeLe' => null]);
        if ($existante !== null) {
            $existante->fusionner($texte);
        } else {
            $this->em->persist(new NotificationEnAttente($destinataire, $type, $texte, $url));
        }
        $this->em->flush();

        return true;
    }

    private function envoyerGenerique(Etudiant|Personnel $destinataire, TypeNotificationEnum $type, string $texte, ?string $url): bool
    {
        $to = self::adresse($destinataire);
        if ($to === null) {
            return false;
        }
        $this->emails->send('unifolio.notification', $to, [
            'destinataire_nom' => $destinataire->getDisplay(),
            'titre' => $type->getRubrique(),
            'texte' => $texte,
            'urlPortal' => $url ?? '/app/unifolio',
        ]);

        return true;
    }

    public static function adresse(Etudiant|Personnel $personne): ?string
    {
        $a = $personne->getMailUniv() ?? $personne->getMailPerso();

        return $a !== null && $a !== '' ? $a : null;
    }
}
