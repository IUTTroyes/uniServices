<?php

namespace App\Notification;

use App\Entity\Notification\Notification;
use App\Entity\Users\Etudiant;
use App\Enum\FrequenceResumeEnum;
use App\Enum\ModeNotificationEnum;
use App\Service\Email\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Résumé groupé : pour chaque personne abonnée à la fréquence demandée, UN seul e-mail pour toute l'application
 * (toutes sources confondues, une rubrique par package et par thème).
 * Les préférences sont relues à l'envoi : un type passé en « jamais » depuis est écarté, jamais envoyé.
 */
class NotificationDigest
{
    /** Lignes détaillées par rubrique ; au-delà, « … et N autres ». */
    private const LIGNES_PAR_RUBRIQUE = 8;
    /** Durée de conservation du journal (la cloche affiche les plus récentes). */
    private const CONSERVATION_JOURS = 90;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EmailService $emails,
        private readonly NotificationService $notifications,
        private readonly NotificationTypeRegistry $types,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return array{personnes: int, envoyes: int, evenements: int, ecartes: int}
     */
    public function envoyer(FrequenceResumeEnum $frequence, bool $essai = false, ?\DateTimeImmutable $maintenant = null): array
    {
        $maintenant ??= new \DateTimeImmutable();
        $stats = ['personnes' => 0, 'envoyes' => 0, 'evenements' => 0, 'ecartes' => 0];

        /** @var Notification[] $attente */
        $attente = $this->em->getRepository(Notification::class)->findBy(['emailTraiteLe' => null], ['misAJourLe' => 'ASC']);
        $parPersonne = [];
        foreach ($attente as $n) {
            $d = $n->getDestinataire();
            $parPersonne[($d instanceof Etudiant ? 'e' : 'p').$d->getId()][] = $n;
        }

        foreach ($parPersonne as $evenements) {
            $destinataire = $evenements[0]->getDestinataire();
            $prefs = $this->notifications->preferences($destinataire);
            if ($prefs->getFrequence() !== $frequence) {
                continue;
            }
            ++$stats['personnes'];

            $retenus = [];
            foreach ($evenements as $n) {
                $type = $this->types->get($n->getType());
                if ($type === null || $prefs->modePour($type) === ModeNotificationEnum::AUCUN) {
                    if (!$essai) {
                        $n->marquerEmailTraite($maintenant);
                    }
                    ++$stats['ecartes'];
                } else {
                    $retenus[] = $n;
                }
            }
            $to = NotificationService::adresse($destinataire);
            if ($retenus === [] || $to === null) {
                if (!$essai) {
                    foreach ($retenus as $n) {
                        $n->marquerEmailTraite($maintenant);
                    }
                }
                continue;
            }

            if (!$essai) {
                try {
                    $this->emails->send('notification.resume', $to, [
                        'destinataire_nom' => $destinataire->getDisplay(),
                        'periode' => $frequence->getPeriode(),
                        'nb_total' => array_sum(array_map(static fn (Notification $n) => $n->getNombre(), $retenus)),
                        'rubriques' => $this->rubriques($retenus),
                        'urlPreferences' => '/app/auth/notifications',
                        'urlPortal' => '/app',
                    ]);
                } catch (\Throwable $e) {
                    // Les événements restent en attente : ils partiront au prochain passage.
                    $this->logger->warning('Échec de l\'envoi d\'un résumé de notifications.', ['erreur' => $e->getMessage()]);
                    continue;
                }
                foreach ($retenus as $n) {
                    $n->marquerEmailTraite($maintenant);
                }
                $prefs->marquerResumeEnvoye($maintenant);
                if ($prefs->getId() === null) {
                    $this->em->persist($prefs);
                }
            }
            ++$stats['envoyes'];
            $stats['evenements'] += \count($retenus);
        }

        if (!$essai) {
            $this->em->flush();
            $this->purger($maintenant);
        }

        return $stats;
    }

    /**
     * @param Notification[] $evenements
     *
     * @return list<array{libelle: string, nombre: int, elements: list<array{texte: string, url: ?string, nombre: int}>, reste: int}>
     */
    private function rubriques(array $evenements): array
    {
        $groupes = [];
        foreach ($evenements as $n) {
            $type = $this->types->get($n->getType());
            $groupes[$type->sourceLibelle.' — '.$type->rubrique][] = $n;
        }
        ksort($groupes);
        $rubriques = [];
        foreach ($groupes as $libelle => $liste) {
            $rubriques[] = [
                'libelle' => $libelle,
                'nombre' => array_sum(array_map(static fn (Notification $n) => $n->getNombre(), $liste)),
                'elements' => array_map(static fn (Notification $n) => ['texte' => $n->getTexte(), 'url' => $n->getUrl(), 'nombre' => $n->getNombre()], \array_slice($liste, 0, self::LIGNES_PAR_RUBRIQUE)),
                'reste' => max(0, \count($liste) - self::LIGNES_PAR_RUBRIQUE),
            ];
        }

        return $rubriques;
    }

    private function purger(\DateTimeImmutable $maintenant): void
    {
        $limite = $maintenant->modify(sprintf('-%d days', self::CONSERVATION_JOURS));
        $this->em->createQuery('DELETE FROM '.Notification::class.' n WHERE n.misAJourLe < :limite AND n.emailTraiteLe IS NOT NULL')->setParameter('limite', $limite)->execute();
    }
}
