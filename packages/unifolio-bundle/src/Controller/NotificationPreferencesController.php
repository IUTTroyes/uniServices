<?php

namespace UnifolioBundle\Controller;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use UnifolioBundle\Entity\NotificationEnAttente;
use UnifolioBundle\Entity\PreferenceNotification;
use UnifolioBundle\Enum\FrequenceResumeEnum;
use UnifolioBundle\Enum\ModeNotificationEnum;
use UnifolioBundle\Enum\TypeNotificationEnum;
use UnifolioBundle\Security\UnifolioVoter;
use UnifolioBundle\Service\Notification\NotificationManager;
use UnifolioBundle\Service\Trace\UnifolioValidationException;

/** Préférences de notification de la personne connectée : interrupteur général, fréquence du résumé, mode par type. */
#[Route('/api/unifolio/notifications/preferences')]
#[IsGranted(UnifolioVoter::ACCES)]
class NotificationPreferencesController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly NotificationManager $manager,
    ) {
    }

    #[Route('', name: 'unifolio_notifications_preferences', methods: ['GET'])]
    public function lire(): JsonResponse
    {
        return $this->json($this->etat($this->manager->preferences($this->personne())));
    }

    #[Route('', name: 'unifolio_notifications_preferences_modifier', methods: ['PUT'])]
    public function modifier(Request $request): JsonResponse
    {
        $personne = $this->personne();
        $prefs = $this->manager->preferences($personne);
        try {
            $d = $request->toArray();
        } catch (\Throwable) {
            throw UnifolioValidationException::un('Requête JSON invalide.');
        }

        try {
            if (\array_key_exists('actif', $d)) {
                $prefs->setActif((bool) $d['actif']);
            }
            if (isset($d['frequence'])) {
                $prefs->setFrequence(FrequenceResumeEnum::tryFrom((string) $d['frequence']) ?? throw new \InvalidArgumentException('Fréquence inconnue.'));
            }
            $permis = TypeNotificationEnum::pourAudience($this->audience($personne));
            foreach ((array) ($d['reglages'] ?? []) as $cle => $mode) {
                $type = TypeNotificationEnum::tryFrom((string) $cle);
                if ($type === null || !\in_array($type, $permis, true)) {
                    throw new \InvalidArgumentException('Type de notification inconnu.');
                }
                $prefs->definirMode($type, ModeNotificationEnum::tryFrom((string) $mode) ?? throw new \InvalidArgumentException('Mode inconnu.'));
            }
        } catch (\InvalidArgumentException $e) {
            throw UnifolioValidationException::un($e->getMessage());
        }
        if ($prefs->getId() === null) {
            $this->em->persist($prefs);
        }
        $this->em->flush();

        return $this->json($this->etat($prefs));
    }

    /** @return array<string, mixed> */
    private function etat(PreferenceNotification $prefs): array
    {
        $personne = $this->personne();
        $cle = $personne instanceof Etudiant ? 'etudiant' : 'personnel';

        return [
            'actif' => $prefs->isActif(),
            'frequence' => ['value' => $prefs->getFrequence()->value, 'libelle' => $prefs->getFrequence()->getLibelle()],
            'frequences' => array_map(static fn (FrequenceResumeEnum $f) => ['value' => $f->value, 'libelle' => $f->getLibelle()], FrequenceResumeEnum::cases()),
            'types' => array_map(static fn (TypeNotificationEnum $t) => [
                'cle' => $t->value,
                'libelle' => $t->getLibelle(),
                'description' => $t->getDescription(),
                // Mode réellement appliqué si l'interrupteur général est actif (le réglage personnel, sinon le défaut).
                'mode' => ($prefs->reglage($t) !== null && \in_array($prefs->reglage($t), $t->getModesAutorises(), true) ? $prefs->reglage($t) : $t->getModeParDefaut())->value,
                'defaut' => $t->getModeParDefaut()->value,
                'modes' => array_map(static fn (ModeNotificationEnum $m) => ['value' => $m->value, 'libelle' => $m->getLibelle()], $t->getModesAutorises()),
            ], TypeNotificationEnum::pourAudience($this->audience($personne))),
            'enAttente' => (int) $this->em->getRepository(NotificationEnAttente::class)->count([$cle => $personne, 'envoyeeLe' => null]),
            'dernierResume' => $prefs->getDernierResumeLe()?->format(\DATE_ATOM),
        ];
    }

    private function personne(): Etudiant|Personnel
    {
        $user = $this->getUser();
        \assert($user instanceof Etudiant || $user instanceof Personnel);

        return $user;
    }

    private function audience(Etudiant|Personnel $personne): string
    {
        return $personne instanceof Etudiant ? TypeNotificationEnum::AUDIENCE_ETUDIANT : TypeNotificationEnum::AUDIENCE_PERSONNEL;
    }
}
