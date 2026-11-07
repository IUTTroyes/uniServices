<?php

namespace UnifolioBundle\Service\Notification;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Service\Email\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use UnifolioBundle\Entity\NotificationEnAttente;
use UnifolioBundle\Entity\PreferenceNotification;
use UnifolioBundle\Enum\FrequenceResumeEnum;
use UnifolioBundle\Enum\ModeNotificationEnum;

/**
 * Envoie, pour chaque personne abonnée à cette fréquence, UN seul e-mail regroupant ce qui s'est passé.
 * Les préférences sont relues à l'envoi : un type passé entre-temps en « jamais » est écarté, jamais envoyé.
 */
class ResumeNotifications
{
    /** Lignes détaillées par rubrique ; au-delà, « … et N autres ». */
    private const LIGNES_PAR_RUBRIQUE = 8;
    /** Conservation des événements déjà traités, et délai au-delà duquel un événement jamais envoyé est abandonné. */
    private const CONSERVATION_JOURS = 60;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EmailService $emails,
        private readonly NotificationManager $manager,
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

        /** @var NotificationEnAttente[] $attente */
        $attente = $this->em->getRepository(NotificationEnAttente::class)->findBy(['envoyeeLe' => null], ['creeeLe' => 'ASC']);
        $parPersonne = [];
        foreach ($attente as $n) {
            $d = $n->getDestinataire();
            $parPersonne[($d instanceof Etudiant ? 'e' : 'p').$d->getId()][] = $n;
        }

        foreach ($parPersonne as $evenements) {
            $destinataire = $evenements[0]->getDestinataire();
            $prefs = $this->manager->preferences($destinataire);
            if ($prefs->getFrequence() !== $frequence) {
                continue;
            }
            ++$stats['personnes'];

            $retenus = [];
            foreach ($evenements as $n) {
                if ($prefs->modePour($n->getType()) === ModeNotificationEnum::AUCUN) {
                    $n->marquerEnvoyee($maintenant);
                    ++$stats['ecartes'];
                } else {
                    $retenus[] = $n;
                }
            }
            $to = NotificationManager::adresse($destinataire);
            if ($retenus === [] || $to === null) {
                if (!$essai) {
                    foreach ($retenus as $n) {
                        $n->marquerEnvoyee($maintenant);
                    }
                }
                continue;
            }

            if (!$essai) {
                try {
                    $this->emails->send('unifolio.resume_notifications', $to, [
                        'destinataire_nom' => $destinataire->getDisplay(),
                        'periode' => $frequence->getPeriode(),
                        'nb_total' => array_sum(array_map(static fn (NotificationEnAttente $n) => $n->getNombre(), $retenus)),
                        'rubriques' => $this->rubriques($retenus),
                        'urlPreferences' => '/app/unifolio/notifications',
                        'urlPortal' => '/app/unifolio',
                    ]);
                } catch (\Throwable $e) {
                    // Les événements restent en attente : ils partiront au prochain passage.
                    $this->logger->warning('UniFolio : échec de l\'envoi d\'un résumé.', ['erreur' => $e->getMessage()]);
                    continue;
                }
                foreach ($retenus as $n) {
                    $n->marquerEnvoyee($maintenant);
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
            $this->purger($maintenant);
            $this->em->flush();
        }

        return $stats;
    }

    /**
     * @param NotificationEnAttente[] $evenements
     *
     * @return list<array{libelle: string, nombre: int, elements: list<array{texte: string, url: ?string, nombre: int}>, reste: int}>
     */
    private function rubriques(array $evenements): array
    {
        $groupes = [];
        foreach ($evenements as $n) {
            $groupes[$n->getType()->getRubrique()][] = $n;
        }
        $rubriques = [];
        foreach ($groupes as $libelle => $liste) {
            $rubriques[] = [
                'libelle' => $libelle,
                'nombre' => array_sum(array_map(static fn (NotificationEnAttente $n) => $n->getNombre(), $liste)),
                'elements' => array_map(static fn (NotificationEnAttente $n) => ['texte' => $n->getTexte(), 'url' => $n->getUrl(), 'nombre' => $n->getNombre()], \array_slice($liste, 0, self::LIGNES_PAR_RUBRIQUE)),
                'reste' => max(0, \count($liste) - self::LIGNES_PAR_RUBRIQUE),
            ];
        }

        return $rubriques;
    }

    private function purger(\DateTimeImmutable $maintenant): void
    {
        $limite = $maintenant->modify(sprintf('-%d days', self::CONSERVATION_JOURS));
        $this->em->createQuery('DELETE FROM '.NotificationEnAttente::class.' n WHERE n.creeeLe < :limite')->setParameter('limite', $limite)->execute();
    }
}
