<?php

namespace UnifolioBundle\Service\Portfolio;

/**
 * Registre des types de blocs d'un portfolio, chargé depuis blocs.json — source unique partagée avec
 * l'éditeur et le rendu (front) : un même schéma définit ce que l'éditeur propose et ce que le serveur accepte.
 */
final class BlocRegistry
{
    /** @var array<string, mixed> */
    private array $schema;

    public function __construct(?string $fichier = null)
    {
        $fichier ??= \dirname(__DIR__, 3) . '/blocs.json';
        $contenu = file_get_contents($fichier);
        if ($contenu === false) {
            throw new \RuntimeException(sprintf('Schéma des blocs introuvable : %s', $fichier));
        }
        $this->schema = json_decode($contenu, true, 512, \JSON_THROW_ON_ERROR);
    }

    /** @return array<string, array<string, mixed>> */
    public function types(): array
    {
        return $this->schema['types'];
    }

    /** @return array<string, mixed>|null */
    public function type(string $type): ?array
    {
        return $this->schema['types'][$type] ?? null;
    }

    public function limite(string $nom): int
    {
        return (int) $this->schema['limites'][$nom];
    }

    /** @return array<string, mixed> */
    public function schema(): array
    {
        return $this->schema;
    }

    /** Types pouvant figurer directement dans une page (les colonnes et volets n'existent que dans leur parent). */
    public function typesRacine(): array
    {
        return array_keys(array_filter($this->schema['types'], static fn (array $t) => !isset($t['seulement_dans'])));
    }
}
