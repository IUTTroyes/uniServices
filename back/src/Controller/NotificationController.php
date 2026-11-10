<?php

namespace App\Controller;

use App\Entity\Notification\Notification;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Enum\FrequenceResumeEnum;
use App\Enum\ModeNotificationEnum;
use App\Notification\NotificationService;
use App\Notification\NotificationType;
use App\Notification\NotificationTypeRegistry;
use App\Security\UserPackageResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * API des notifications de la personne connectée (étudiant ou personnel) : cloche, lecture, préférences.
 * Ne livre que ses propres notifications.
 */
#[Route('/api/notifications')]
#[IsGranted('IS_AUTHENTICATED')]
class NotificationController extends AbstractController
{
    private const LIMITE_PAR_DEFAUT = 30;
    private const LIMITE_MAX = 50;
    private const UUID = '[0-9a-fA-F-]{36}';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly NotificationService $notifications,
        private readonly NotificationTypeRegistry $types,
        private readonly UserPackageResolver $packages,
    ) {
    }

    #[Route('', name: 'api_notifications_liste', methods: ['GET'])]
    public function liste(Request $request): JsonResponse
    {
        $limite = min(self::LIMITE_MAX, max(1, (int) $request->query->get('limite', self::LIMITE_PAR_DEFAUT)));
        $criteres = [$this->champ() => $this->personne()];
        if ($request->query->getBoolean('nonLues')) {
            $criteres['lueLe'] = null;
        }
        $liste = $this->em->getRepository(Notification::class)->findBy($criteres, ['misAJourLe' => 'DESC', 'id' => 'DESC'], $limite);

        return $this->json(['notifications' => array_map($this->normaliser(...), $liste), 'nonLues' => $this->nonLues()]);
    }

    /** Compteur seul : léger, pour la pastille de la cloche. */
    #[Route('/compteur', name: 'api_notifications_compteur', methods: ['GET'])]
    public function compteur(): JsonResponse
    {
        return $this->json(['nonLues' => $this->nonLues()]);
    }

    #[Route('/lues', name: 'api_notifications_toutes_lues', methods: ['POST'])]
    public function toutesLues(): JsonResponse
    {
        $maintenant = new \DateTimeImmutable();
        $this->em->createQuery('UPDATE '.Notification::class.' n SET n.lueLe = :maintenant WHERE n.'.$this->champ().' = :personne AND n.lueLe IS NULL')
            ->setParameter('maintenant', $maintenant)->setParameter('personne', $this->personne())->execute();

        return $this->json(['nonLues' => 0]);
    }

    #[Route('/{uuid}/lue', name: 'api_notifications_lue', requirements: ['uuid' => self::UUID], methods: ['POST'])]
    public function lue(string $uuid): JsonResponse
    {
        $notification = $this->em->getRepository(Notification::class)->findOneBy(['uuid' => $uuid]);
        if ($notification === null || !$notification->appartientA($this->personne())) {
            throw $this->createNotFoundException('Notification introuvable.');
        }
        $notification->marquerLue();
        $this->em->flush();

        return $this->json(['nonLues' => $this->nonLues()]);
    }

    #[Route('/preferences', name: 'api_notifications_preferences', methods: ['GET'])]
    public function preferences(): JsonResponse
    {
        return $this->json($this->etatPreferences());
    }

    #[Route('/preferences', name: 'api_notifications_preferences_modifier', methods: ['PUT'])]
    public function modifierPreferences(Request $request): JsonResponse
    {
        $personne = $this->personne();
        $prefs = $this->notifications->preferences($personne);
        try {
            $d = $request->toArray();
        } catch (\Throwable) {
            return $this->json(['message' => 'Requête JSON invalide.', 'erreurs' => ['Requête JSON invalide.']], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            if (\array_key_exists('actif', $d)) {
                $prefs->setActif((bool) $d['actif']);
            }
            if (isset($d['frequence'])) {
                $prefs->setFrequence(FrequenceResumeEnum::tryFrom((string) $d['frequence']) ?? throw new \InvalidArgumentException('Fréquence inconnue.'));
            }
            $permis = $this->typesPermis();
            foreach ((array) ($d['reglages'] ?? []) as $cle => $mode) {
                $type = $permis[(string) $cle] ?? throw new \InvalidArgumentException('Type de notification inconnu.');
                $prefs->definirMode($type, ModeNotificationEnum::tryFrom((string) $mode) ?? throw new \InvalidArgumentException('Mode inconnu.'));
            }
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage(), 'erreurs' => [$e->getMessage()]], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($prefs->getId() === null) {
            $this->em->persist($prefs);
        }
        $this->em->flush();

        return $this->json($this->etatPreferences());
    }

    /** @return array<string, mixed> */
    private function etatPreferences(): array
    {
        $prefs = $this->notifications->preferences($this->personne());

        return [
            'actif' => $prefs->isActif(),
            'frequence' => ['value' => $prefs->getFrequence()->value, 'libelle' => $prefs->getFrequence()->getLibelle()],
            'frequences' => array_map(static fn (FrequenceResumeEnum $f) => ['value' => $f->value, 'libelle' => $f->getLibelle()], FrequenceResumeEnum::cases()),
            'types' => array_values(array_map(static fn (NotificationType $t) => [
                'cle' => $t->cle,
                'source' => ['cle' => $t->source, 'libelle' => $t->sourceLibelle],
                'libelle' => $t->libelle,
                'description' => $t->description,
                'mode' => $prefs->modeAffiche($t)->value,
                'defaut' => $t->modeParDefaut->value,
                'modes' => array_map(static fn (ModeNotificationEnum $m) => ['value' => $m->value, 'libelle' => $m->getLibelle()], $t->modes()),
            ], $this->typesPermis())),
            'enAttente' => (int) $this->em->getRepository(Notification::class)->count([$this->champ() => $this->personne(), 'emailTraiteLe' => null]),
            'dernierResume' => $prefs->getDernierResumeLe()?->format(\DATE_ATOM),
        ];
    }

    /**
     * Types proposés à la personne connectée : ceux de son public ET des packages dont elle dispose
     * (un étudiant sans UniFolio ne voit pas les réglages d'UniFolio).
     *
     * @return array<string, NotificationType>
     */
    private function typesPermis(): array
    {
        $personne = $this->personne();
        $audience = $personne instanceof Etudiant ? NotificationType::AUDIENCE_ETUDIANT : NotificationType::AUDIENCE_PERSONNEL;
        $permis = [];
        foreach ($this->types->pourAudience($audience) as $t) {
            if ($this->packages->hasPackage($personne, $t->source)) {
                $permis[$t->cle] = $t;
            }
        }

        return $permis;
    }

    /** @return array<string, mixed> */
    private function normaliser(Notification $n): array
    {
        $type = $this->types->get($n->getType());

        return [
            'uuid' => $n->getUuidString(),
            'source' => ['cle' => $n->getSource(), 'libelle' => $type?->sourceLibelle ?? ucfirst($n->getSource())],
            'type' => $n->getType(),
            'texte' => $n->getTexte(),
            'url' => $n->getUrl(),
            'nombre' => $n->getNombre(),
            'lue' => $n->isLue(),
            'date' => $n->getMisAJourLe()->format(\DATE_ATOM),
        ];
    }

    private function nonLues(): int
    {
        return (int) $this->em->getRepository(Notification::class)->count([$this->champ() => $this->personne(), 'lueLe' => null]);
    }

    private function champ(): string
    {
        return $this->personne() instanceof Etudiant ? 'etudiant' : 'personnel';
    }

    private function personne(): Etudiant|Personnel
    {
        $user = $this->getUser();
        \assert($user instanceof Etudiant || $user instanceof Personnel);

        return $user;
    }
}
