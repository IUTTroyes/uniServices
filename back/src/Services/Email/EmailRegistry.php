<?php

namespace App\Services\Email;

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

    /**
     * @param iterable<AbstractEmailDefinition> $definitions
     */
    public function __construct(iterable $definitions)
    {
        foreach ($definitions as $definition) {
            $this->definitions[$definition->getKey()] = $definition;
        }
    }

    /**
     * Retourne une définition par sa clé, ou null si inconnue.
     */
    public function get(string $key): ?AbstractEmailDefinition
    {
        return $this->definitions[$key] ?? null;
    }

    /**
     * Retourne toutes les définitions, indexées par clé.
     *
     * @return array<string, AbstractEmailDefinition>
     */
    public function all(): array
    {
        return $this->definitions;
    }

    /**
     * Retourne toutes les définitions groupées par bundle.
     *
     * @return array<string, AbstractEmailDefinition[]>
     */
    public function groupedByBundle(): array
    {
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
        return isset($this->definitions[$key]);
    }
}
