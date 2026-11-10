<?php

namespace App\Service\Email;

use App\Notification\NotificationEmailDefinition;
use App\Notification\NotificationTypeRegistry;

/**
 * Registre centralisé de toutes les définitions d'emails disponibles dans l'application.
 *
 * Les services taggés `app.email_definition` sont automatiquement injectés ici
 * grâce à la configuration dans services.yaml.
 */
final class EmailRegistry
{
    /**
     * @var array<string, AbstractEmailDefinition> Indexé par clé d'email
     */
    private array $definitions = [];

    private bool $notificationsChargees = false;

    /**
     * @param iterable<AbstractEmailDefinition> $definitions définitions déclarées par le cœur et les packages
     * @param NotificationTypeRegistry|null     $notifications chaque type de notification sans e-mail dédié (même clé)
     *                                                         reçoit un e-mail paramétrable (NotificationEmailDefinition)
     */
    public function __construct(iterable $definitions, private readonly ?NotificationTypeRegistry $notifications = null)
    {
        foreach ($definitions as $definition) {
            $this->definitions[$definition->getKey()] = $definition;
        }
    }

    /** Les e-mails des notifications sont ajoutés au premier usage (les types sont déclarés par les packages). */
    private function chargerNotifications(): void
    {
        if ($this->notifications === null || $this->notificationsChargees) {
            return;
        }
        $this->notificationsChargees = true;
        foreach ($this->notifications->all() as $type) {
            $this->definitions[$type->cle] ??= new NotificationEmailDefinition($type);
        }
    }

    /**
     * Retourne une définition par sa clé, ou null si inconnue.
     */
    public function get(string $key): ?AbstractEmailDefinition
    {
        $this->chargerNotifications();

        return $this->definitions[$key] ?? null;
    }

    /**
     * Retourne toutes les définitions, indexées par clé.
     *
     * @return array<string, AbstractEmailDefinition>
     */
    public function all(): array
    {
        $this->chargerNotifications();

        return $this->definitions;
    }

    /**
     * Retourne toutes les définitions groupées par bundle.
     *
     * @return array<string, AbstractEmailDefinition[]>
     */
    public function groupedByBundle(): array
    {
        $this->chargerNotifications();
        $grouped = [];
        foreach ($this->definitions as $definition) {
            $grouped[$definition->getBundle()][] = $definition;
        }
        ksort($grouped);

        return $grouped;
    }

    /**
     * Vérifie si une clé d'email est connue.
     */
    public function has(string $key): bool
    {
        $this->chargerNotifications();

        return isset($this->definitions[$key]);
    }
}
