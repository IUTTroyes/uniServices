<?php

namespace App\Notification;

use App\Enum\ModeNotificationEnum;

/**
 * Déclaration d'un type de notification par un package (voir docs/NOTIFICATIONS_SYSTEM.md).
 * La clé est unique dans toute l'application : « <package>.<evenement> », ex. « unifolio.commentaire_enseignant ».
 */
final readonly class NotificationType
{
    public const AUDIENCE_ETUDIANT = 'etudiant';
    public const AUDIENCE_PERSONNEL = 'personnel';

    /**
     * @param list<ModeNotificationEnum>|null $modesAutorises null = les trois modes ; un type ne peut pas être ignoré
     *                                                        ou regroupé quand le métier l'interdit
     */
    public function __construct(
        public string $cle,
        /** Identifiant du package émetteur (« unifolio »). */
        public string $source,
        /** Nom du package affiché à l'utilisateur (« UniFolio »). */
        public string $sourceLibelle,
        /** Phrase affichée dans les préférences : « Un enseignant commente une de mes traces ». */
        public string $libelle,
        public string $description,
        /** Titre de la rubrique dans le résumé groupé : « Nouveaux commentaires ». */
        public string $rubrique,
        public string $audience,
        public ModeNotificationEnum $modeParDefaut = ModeNotificationEnum::RESUME,
        public ?array $modesAutorises = null,
    ) {
        if (!preg_match('/^[a-z0-9_]+\.[a-z0-9_]+$/', $cle)) {
            throw new \InvalidArgumentException(sprintf('Clé de notification invalide « %s » : attendu « package.evenement » en minuscules.', $cle));
        }
        if (!str_starts_with($cle, $source.'.')) {
            throw new \InvalidArgumentException(sprintf('La clé « %s » doit commencer par « %s. ».', $cle, $source));
        }
        if (!\in_array($audience, [self::AUDIENCE_ETUDIANT, self::AUDIENCE_PERSONNEL], true)) {
            throw new \InvalidArgumentException(sprintf('Public inconnu « %s ».', $audience));
        }
        if (!\in_array($modeParDefaut, $this->modes(), true)) {
            throw new \InvalidArgumentException(sprintf('Le mode par défaut de « %s » n\'est pas autorisé pour ce type.', $cle));
        }
    }

    /** @return list<ModeNotificationEnum> */
    public function modes(): array
    {
        return $this->modesAutorises ?? ModeNotificationEnum::cases();
    }

    public function autorise(ModeNotificationEnum $mode): bool
    {
        return \in_array($mode, $this->modes(), true);
    }
}
